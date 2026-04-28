<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{
    public function registrar(
        string $acao,
        ?object $entidade = null,
        ?array $antes = null,
        ?array $depois = null,
        ?string $descricao = null,
        ?int $empresaIdOverride = null
    ): AuditoriaLog {
        $user = Auth::guard('web')->user();

        return AuditoriaLog::create([
            'user_id' => $user?->id,
            'empresa_id' => $empresaIdOverride
                ?? $user?->empresa_id
                ?? ($entidade?->empresa_id ?? null),
            'acao' => $acao,
            'entidade' => $entidade ? get_class($entidade) : null,
            'entidade_id' => $entidade?->id,
            'antes' => $antes,
            'depois' => $depois,
            'descricao' => $descricao,
            'ip' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
