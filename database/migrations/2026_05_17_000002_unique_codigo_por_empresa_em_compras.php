<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique (empresa_id, codigo) em compras — idempotency key do PDV.
 *
 * Bug antes: PDV externo (`POST /api/v1/pdv/{slug}/compras`) aceita
 * `codigo` no payload mas nada checa duplicata. Se o PDV reenviar
 * (timeout do client e retry, integração bugada), a mesma venda
 * vira N compras com mesmo `codigo`, creditando pontos/cashback
 * múltiplas vezes. `codigo` é justamente a chave que o operador do
 * PDV deve mandar pra evitar isso (id da venda na ERP).
 *
 * Unique parcial só onde codigo IS NOT NULL — MySQL/MariaDB permite
 * múltiplos NULLs em coluna nullable. Cleanup prévio: zera codigo de
 * duplicatas mais novas (mantém o mais antigo) — operador resolve
 * manualmente depois.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Limpa duplicatas: deixa só o mais antigo, zera codigo dos demais
        $duplicatas = DB::table('compras')
            ->select('empresa_id', 'codigo', DB::raw('MIN(id) as primeiro_id'), DB::raw('COUNT(*) as qtd'))
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->groupBy('empresa_id', 'codigo')
            ->having('qtd', '>', 1)
            ->get();

        foreach ($duplicatas as $dup) {
            DB::table('compras')
                ->where('empresa_id', $dup->empresa_id)
                ->where('codigo', $dup->codigo)
                ->where('id', '!=', $dup->primeiro_id)
                ->update(['codigo' => null]);
        }

        Schema::table('compras', function (Blueprint $table) {
            $table->unique(['empresa_id', 'codigo'], 'compras_empresa_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropUnique('compras_empresa_codigo_unique');
        });
    }
};
