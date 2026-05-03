<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\OtpCodigo;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    /**
     * POST /api/v1/auth/otp/solicitar
     * Body: { telefone, empresa_slug }
     * Gera código de 6 dígitos e envia via WhatsApp.
     */
    public function solicitar(Request $request, WhatsappService $whatsapp)
    {
        $dados = $request->validate([
            'telefone' => 'required|string|max:20',
            'empresa_slug' => 'required|string',
        ]);

        $empresa = Empresa::where('slug', $dados['empresa_slug'])->where('ativo', true)->firstOrFail();

        // Normaliza pra dígitos-only — throttle e invalidação ficam consistentes
        // independentemente do formato que o usuário digitou.
        $telefoneDigits = preg_replace('/\D/', '', $dados['telefone']);

        $cliente = Cliente::whereTelefone($dados['telefone'])
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->first();

        if (!$cliente) {
            throw ValidationException::withMessages(['telefone' => 'Cliente não encontrado nesta empresa.']);
        }

        // Throttle: max 3 códigos em 15 min
        $recentes = OtpCodigo::where('empresa_id', $empresa->id)
            ->where('telefone', $telefoneDigits)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
        if ($recentes >= 3) {
            return response()->json([
                'message' => 'Muitas tentativas. Aguarde 15 minutos.',
            ], 429);
        }

        // Invalida códigos anteriores não usados
        OtpCodigo::where('empresa_id', $empresa->id)
            ->where('telefone', $telefoneDigits)
            ->where('usado', false)
            ->update(['usado' => true]);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCodigo::create([
            'empresa_id' => $empresa->id,
            'telefone' => $telefoneDigits,
            'codigo' => $codigo,
            'expires_at' => now()->addMinutes(5),
            'ip' => $request->ip(),
        ]);

        $textoFallback = "🔐 Seu código {$empresa->nome}: *{$codigo}*\n\nVálido por 5 minutos.\nNão compartilhe com ninguém.";
        $whatsapp->enviarEvento(
            $empresa,
            $cliente->telefone,
            'otp',
            ['codigo' => $codigo],
            $textoFallback
        );

        return response()->json([
            'message' => 'Código enviado via WhatsApp.',
            'expira_em_segundos' => 300,
            // em modo mock, devolve o código pra facilitar dev
            'codigo_dev' => $empresa->whatsapp_provider === 'mock' || !$empresa->whatsapp_ativo ? $codigo : null,
        ]);
    }

    /**
     * POST /api/v1/auth/otp/validar
     * Body: { telefone, codigo, empresa_slug }
     * Valida código e retorna token Sanctum.
     */
    public function validar(Request $request)
    {
        $dados = $request->validate([
            'telefone' => 'required|string|max:20',
            'codigo' => 'required|string|size:6',
            'empresa_slug' => 'required|string',
        ]);

        $empresa = Empresa::where('slug', $dados['empresa_slug'])->where('ativo', true)->firstOrFail();
        $telefoneDigits = preg_replace('/\D/', '', $dados['telefone']);

        $otp = OtpCodigo::where('empresa_id', $empresa->id)
            ->where('telefone', $telefoneDigits)
            ->where('usado', false)
            ->latest()
            ->first();

        if (!$otp) {
            throw ValidationException::withMessages(['codigo' => 'Solicite um novo código.']);
        }

        if ($otp->expirado()) {
            $otp->update(['usado' => true]);
            throw ValidationException::withMessages(['codigo' => 'Código expirado. Solicite um novo.']);
        }

        $otp->increment('tentativas');
        if ($otp->tentativas > 5) {
            $otp->update(['usado' => true]);
            throw ValidationException::withMessages(['codigo' => 'Muitas tentativas. Solicite um novo código.']);
        }

        if (!hash_equals($otp->codigo, $dados['codigo'])) {
            throw ValidationException::withMessages(['codigo' => 'Código incorreto.']);
        }

        $otp->update(['usado' => true]);

        $cliente = Cliente::whereTelefone($dados['telefone'])
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->firstOrFail();

        $cliente->update(['ultimo_acesso' => now(), 'ultimo_ip' => $request->ip()]);
        $token = $cliente->createToken('pwa-cliente-otp')->plainTextToken;

        return response()->json([
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'telefone' => $cliente->telefone,
                'email' => $cliente->email,
                'pontos' => (float) $cliente->pontos_atual,
                'cashback' => (float) $cliente->cashback_atual,
                'codigo_qr' => $cliente->codigo_qr,
                'codigo_indicacao' => $cliente->codigo_indicacao,
                'total_compras' => $cliente->total_compras,
                'total_gasto' => (float) $cliente->total_gasto,
            ],
            'empresa' => [
                'id' => $empresa->id,
                'slug' => $empresa->slug,
                'nome' => $empresa->nome,
                'logo' => $empresa->logo ? asset('storage/'.$empresa->logo) : null,
                'cor_primaria' => $empresa->cor_primaria,
                'cor_secundaria' => $empresa->cor_secundaria,
                'pontos_por_real' => (float) $empresa->pontos_por_real,
                'cashback_percentual' => (float) $empresa->cashback_percentual,
            ],
        ]);
    }
}
