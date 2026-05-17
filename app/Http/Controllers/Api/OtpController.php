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

        // Throttle aplicado SEMPRE — mesmo para telefones não cadastrados —
        // para evitar enumeração via análise de tempo de resposta.
        $maxOtps = (int) (\App\Models\ConfiguracaoSistema::instancia()->otp_max_por_telefone ?: 3);
        $recentes = OtpCodigo::where('empresa_id', $empresa->id)
            ->where('telefone', $telefoneDigits)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
        if ($recentes >= $maxOtps) {
            return response()->json([
                'message' => 'Muitas tentativas. Aguarde 15 minutos.',
            ], 429);
        }

        $cliente = Cliente::whereTelefone($dados['telefone'])
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->first();

        // Resposta genérica: nunca revelar se o telefone existe (anti-enumeração).
        // Se não existe, fingimos sucesso sem enviar nada.
        if (!$cliente) {
            return response()->json([
                'message' => 'Se o telefone estiver cadastrado, você receberá um código por WhatsApp.',
                'expira_em_segundos' => 300,
                'codigo_dev' => null,
            ]);
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

        // codigo_dev SÓ em ambiente local — facilita teste sem WhatsApp real.
        // Bug crítico anterior: lógica lia $empresa->whatsapp_ativo (campo
        // legado, default false após a config global ter migrado pra
        // ConfiguracaoSistema). Em produção real, !$empresa->whatsapp_ativo
        // era true → response vazava o OTP em texto puro pra qualquer um
        // que conhecia o telefone, bypassando o 2FA completamente.
        return response()->json([
            'message' => 'Código enviado via WhatsApp.',
            'expira_em_segundos' => 300,
            'codigo_dev' => app()->environment('local') ? $codigo : null,
        ]);
    }

    /**
     * POST /api/v1/auth/recuperar-senha
     * Body: { telefone, codigo, senha_nova, empresa_slug }
     * Valida OTP, troca a senha do cliente e retorna token (loga).
     * Endpoint único pra evitar gastar 2 chamadas (e o código fica
     * marcado como usado só uma vez).
     */
    public function recuperarSenha(Request $request)
    {
        $dados = $request->validate([
            'telefone' => 'required|string|max:20',
            'codigo' => 'required|string|size:6',
            'senha_nova' => 'required|string|min:8|confirmed',
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

        // Anti-DOS: incrementa tentativas SOMENTE quando o código está errado.
        // Antes incrementávamos cego e o limite estourava com 3 chamadas
        // erradas vindas de um atacante que só queria invalidar o OTP de uma
        // vítima legítima (a vítima nunca conseguia recuperar a senha).
        $maxTentativas = (int) (\App\Models\ConfiguracaoSistema::instancia()->otp_max_tentativas ?: 3);
        if (!hash_equals($otp->codigo, $dados['codigo'])) {
            $otp->increment('tentativas');
            if ($otp->tentativas >= $maxTentativas) {
                $otp->update(['usado' => true]);
                throw ValidationException::withMessages(['codigo' => 'Muitas tentativas. Solicite um novo código.']);
            }
            throw ValidationException::withMessages(['codigo' => 'Código incorreto.']);
        }

        $otp->update(['usado' => true]);

        $cliente = Cliente::whereTelefone($dados['telefone'])
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->firstOrFail();

        $cliente->update([
            'password' => Hash::make($dados['senha_nova']),
            'senha_temporaria' => false,
            'ultimo_acesso' => now(),
            'ultimo_ip' => $request->ip(),
        ]);

        // Invalida TODOS os tokens existentes — se algum estava vazado, expira agora.
        $cliente->tokens()->delete();

        $token = $cliente->createToken('pwa-cliente-recovery')->plainTextToken;

        return response()->json([
            'message' => 'Senha redefinida com sucesso!',
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
                'senha_temporaria' => false,
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

        // Anti-DOS: ver comentário equivalente em recuperarSenha. Incrementa
        // só no caminho de código errado.
        $maxTentativas = (int) (\App\Models\ConfiguracaoSistema::instancia()->otp_max_tentativas ?: 3);
        if (!hash_equals($otp->codigo, $dados['codigo'])) {
            $otp->increment('tentativas');
            if ($otp->tentativas >= $maxTentativas) {
                $otp->update(['usado' => true]);
                throw ValidationException::withMessages(['codigo' => 'Muitas tentativas. Solicite um novo código.']);
            }
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
