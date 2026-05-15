<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Coluna pré-computada `telefone_digits` (só dígitos) + índice composto
 * (empresa_id, telefone_digits). Sem isso, o scope `whereTelefone()`
 * fazia `REPLACE×4` em whereRaw a cada login/OTP — full table scan que
 * em bases com 100k+ clientes derrubava o tempo de resposta de auth
 * de ~50ms pra ~1s.
 *
 * Estratégia:
 *   - Adiciona coluna
 *   - Popula com `REGEXP_REPLACE(telefone, '[^0-9]', '')` (MySQL 8+) ou
 *     fallback PHP via loop chunked pra MySQL 5.7
 *   - Adiciona índice
 *   - Observer (em PHP) mantém atualizado dali pra frente
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clientes')) {
            return;
        }

        if (!Schema::hasColumn('clientes', 'telefone_digits')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('telefone_digits', 20)
                    ->nullable()
                    ->after('telefone')
                    ->comment('telefone com só dígitos; mantido pelo ClienteObserver');
            });
        }

        // Popula em uma única query usando REGEXP_REPLACE quando suportado;
        // fallback chunked se não suportar (raro hoje, mas Cobrir).
        try {
            DB::statement("UPDATE clientes SET telefone_digits = REGEXP_REPLACE(telefone, '[^0-9]', '') WHERE telefone IS NOT NULL");
        } catch (\Throwable $e) {
            // Fallback chunked PHP — REGEXP_REPLACE precisa MySQL 8+ / MariaDB 10.0.5+
            DB::table('clientes')
                ->whereNotNull('telefone')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) {
                    foreach ($rows as $r) {
                        $digits = preg_replace('/\D/', '', (string) $r->telefone);
                        DB::table('clientes')->where('id', $r->id)->update(['telefone_digits' => $digits]);
                    }
                });
        }

        // Índice composto: a maioria das queries por telefone também
        // filtra por empresa_id (single-tenant scope).
        try {
            Schema::table('clientes', function (Blueprint $table) {
                $table->index(['empresa_id', 'telefone_digits'], 'clientes_empresa_tel_digits_idx');
            });
        } catch (\Throwable $e) {
            // Índice pode já existir em reruns parciais — ignora.
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            try { $table->dropIndex('clientes_empresa_tel_digits_idx'); } catch (\Throwable $e) {}
            $table->dropColumn('telefone_digits');
        });
    }
};
