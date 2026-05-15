<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia 403 quando um Cliente autenticado ainda tem senha_temporaria=true.
 *
 * O PWA já redireciona o cliente para a tela de troca após o login, mas isso
 * é UX no client-side: um atacante que conhece o telefone de outro cliente
 * consegue token via POST /api/v1/auth/login e pode chamar rotas internas
 * direto, ignorando o redirect. Este middleware fecha esse caminho no
 * servidor — só passa quem já definiu a senha definitiva.
 *
 * Whitelist (aplicada por NÃO incluir o middleware na rota):
 *   - GET  /auth/me         — PWA precisa pra renderizar tela de troca
 *   - POST /auth/logout     — sempre deixar sair
 *   - PUT  /cliente/senha   — endpoint da própria troca
 *
 * Não afeta User da loja (operador) — só dispara quando o auth()->user()
 * é um Cliente.
 */
class RequirePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Cliente && $user->senha_temporaria) {
            return response()->json([
                'error'   => 'password_change_required',
                'message' => 'Defina uma senha definitiva antes de continuar.',
            ], 403);
        }

        return $next($request);
    }
}
