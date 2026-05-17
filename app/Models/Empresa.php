<?php

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use HasFactory, Auditavel;

    protected $table = 'empresas';

    protected $fillable = [
        'nome', 'slug', 'cnpj', 'telefone', 'email', 'endereco', 'logo',
        'logo_bg_color', 'logo_scale',
        'cor_primaria', 'cor_secundaria', 'pontos_por_real',
        'cashback_percentual', 'modo_fidelidade',
        'dias_liberar_cashback', 'validade_pontos_dias', 'ativo', 'setup_concluido', 'setup_passos_vistos',
        'pdv_secret', 'plano_id',
        'whatsapp_provider', 'whatsapp_api_url', 'whatsapp_api_token',
        'whatsapp_instance', 'whatsapp_phone_id', 'whatsapp_ativo',
        'whatsapp_webhook_verify_token', 'whatsapp_waba_id',
    ];

    public const MODOS_FIDELIDADE = [
        'pontos'   => 'Apenas pontos',
        'cashback' => 'Apenas cashback',
        'ambos'    => 'Pontos + cashback',
    ];

    public function usaPontos(): bool
    {
        return in_array($this->modo_fidelidade ?? 'ambos', ['pontos', 'ambos'], true);
    }

    public function usaCashback(): bool
    {
        return in_array($this->modo_fidelidade ?? 'ambos', ['cashback', 'ambos'], true);
    }

    protected $casts = [
        'pontos_por_real' => 'decimal:2',
        'cashback_percentual' => 'decimal:2',
        'ativo' => 'boolean',
        'whatsapp_ativo' => 'boolean',
        'setup_concluido' => 'boolean',
        'setup_passos_vistos' => 'array',
        // pdv_secret é credencial de PDV externo (header X-Pdv-Secret). Em
        // plain text no DB, SQL injection ou backup leak expõe creds de PDV
        // de TODAS empresas. Cast 'encrypted' guarda cifrado com APP_KEY —
        // backup do banco isolado não é mais útil pro atacante. Admin/super
        // continuam vendo o valor cru na UI (descriptografado em runtime).
        'pdv_secret' => 'encrypted',
        // whatsapp_api_token também é credencial sensível (auth com gateway).
        'whatsapp_api_token' => 'encrypted',
        'whatsapp_webhook_verify_token' => 'encrypted',
    ];

    public function passoVisto(string $chave): bool
    {
        return in_array($chave, $this->setup_passos_vistos ?? [], true);
    }

    public function marcarPassoVisto(string $chave): void
    {
        // Race write em JSON: 2 requests paralelos (AIGrowthController e
        // PwaShareController marcando passos diferentes) faziam
        // read-modify-write sem lock — um dos passos era perdido.
        // lockForUpdate + re-read dentro da transaction força
        // serialização.
        \Illuminate\Support\Facades\DB::transaction(function () use ($chave) {
            $lockada = static::lockForUpdate()->find($this->id);
            if (!$lockada) return;

            $vistos = $lockada->setup_passos_vistos ?? [];
            if (in_array($chave, $vistos, true)) return;

            $vistos[] = $chave;
            $lockada->setup_passos_vistos = $vistos;
            $lockada->save();

            // Sync model em memória (caller espera que $this->passoVisto()
            // retorne true em seguida).
            $this->setup_passos_vistos = $vistos;
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            if (empty($empresa->slug)) {
                $empresa->slug = Str::slug($empresa->nome);
            }
            if (empty($empresa->pdv_secret)) {
                $empresa->pdv_secret = 'sk_'.Str::random(40);
            }
        });
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class);
    }

    public function assinatura()
    {
        // hasOne sem ordering pegava a 1ª por ordem do MySQL — empresa
        // com múltiplas assinaturas (EmpresaObserver cria uma trial +
        // super admin cria outra manual) ficava com a "errada". latest
        // garante que vem sempre a mais recente.
        return $this->hasOne(Assinatura::class)
            ->whereNotIn('status', ['cancelada'])
            ->latest('id');
    }

    /**
     * Verifica se a empresa tem acesso ao módulo. Lê do plano via assinatura
     * ativa; sem assinatura cai no plano default (campo $empresa->plano_id);
     * sem nada disponível, libera tudo (modo "instalação", evita travar).
     */
    public function temModulo(string $chave): bool
    {
        $plano = $this->assinatura?->plano ?? $this->plano;
        if (!$plano) return true;
        return $plano->temModulo($chave);
    }

    /**
     * Última assinatura criada pela empresa, INCLUINDO canceladas. A
     * relação `assinatura()` filtra `cancelada` pq quase todos os usos
     * dela esperam um plano ativo (PixDriver, módulos, MeuPlano UI).
     * statusInadimplencia/diasAtraso, ao contrário, precisam enxergar a
     * cancelada pra retornar `bloqueio_total` em vez de `sem_assinatura`.
     * Cache simples em memória pra não repetir a query no mesmo request.
     */
    private ?Assinatura $assinaturaMaisRecenteCache = null;
    private bool $assinaturaMaisRecenteResolvida = false;

    public function assinaturaMaisRecente(): ?Assinatura
    {
        if (!$this->assinaturaMaisRecenteResolvida) {
            $this->assinaturaMaisRecenteCache = Assinatura::where('empresa_id', $this->id)
                ->latest('id')
                ->first();
            $this->assinaturaMaisRecenteResolvida = true;
        }
        return $this->assinaturaMaisRecenteCache;
    }

    /**
     * Status financeiro pra controle de bloqueio gradual:
     *   em_dia         → tudo liberado
     *   trial          → tudo liberado, banner informativo
     *   aviso          → 0-7 dias após vencimento, libera tudo
     *   bloqueio_parcial → 8-30 dias, bloqueia módulos avançados
     *   bloqueio_total   → 30+ dias OU status='cancelada'/'pausada'
     *   sem_assinatura → nunca teve, libera (modo "instalação")
     */
    public function statusInadimplencia(): string
    {
        $a = $this->assinaturaMaisRecente();
        if (!$a) return 'sem_assinatura';

        if (in_array($a->status, ['cancelada', 'pausada'], true)) return 'bloqueio_total';
        if ($a->emTrial()) return 'trial';
        if (!$a->proximo_vencimento) return 'em_dia';

        $hoje = now()->startOfDay();
        $venc = $a->proximo_vencimento->startOfDay();
        if ($venc->gte($hoje)) return 'em_dia';

        $diasAtraso = (int) $venc->diffInDays($hoje);
        if ($diasAtraso <= 7)  return 'aviso';
        if ($diasAtraso <= 30) return 'bloqueio_parcial';
        return 'bloqueio_total';
    }

    public function diasAtraso(): int
    {
        $a = $this->assinaturaMaisRecente();
        if (!$a || !$a->proximo_vencimento) return 0;
        $venc = $a->proximo_vencimento->startOfDay();
        $hoje = now()->startOfDay();
        return $venc->lt($hoje) ? (int) $venc->diffInDays($hoje) : 0;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function recompensas(): HasMany
    {
        return $this->hasMany(Recompensa::class);
    }

    public function resgates(): HasMany
    {
        return $this->hasMany(Resgate::class);
    }

    public function regrasPontuacao(): HasMany
    {
        return $this->hasMany(RegraPontuacao::class);
    }

    public function campanhas(): HasMany
    {
        return $this->hasMany(Campanha::class);
    }
}
