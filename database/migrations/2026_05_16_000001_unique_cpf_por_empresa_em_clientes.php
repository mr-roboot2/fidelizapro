<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona UNIQUE(empresa_id, cpf) em clientes (ignorando NULL — comportamento
 * padrão do MySQL/MariaDB pra unique indexes com colunas nullable).
 *
 * Antes desta migration o backend tinha dedupe APENAS no controller
 * (Cliente::where('empresa_id', X)->where('cpf', Y)->exists() antes do
 * insert). Vulnerável a race entre check e insert: 2 requests simultâneas
 * com mesmo CPF criavam 2 clientes duplicados na mesma empresa.
 *
 * Cleanup prévio: deixa só o registro mais antigo de cada (empresa_id, cpf)
 * mantendo o cpf; nos demais zera o cpf (mantém o cliente porque pode ter
 * histórico de compras/pontos). Operador resolve manualmente depois.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Detecta duplicatas e zera CPF de todos menos o mais antigo.
        //    Não deleta — cliente pode ter histórico.
        $duplicatas = DB::table('clientes')
            ->select('empresa_id', 'cpf', DB::raw('MIN(id) as primeiro_id'), DB::raw('COUNT(*) as qtd'))
            ->whereNotNull('cpf')
            ->where('cpf', '!=', '')
            ->groupBy('empresa_id', 'cpf')
            ->having('qtd', '>', 1)
            ->get();

        foreach ($duplicatas as $dup) {
            DB::table('clientes')
                ->where('empresa_id', $dup->empresa_id)
                ->where('cpf', $dup->cpf)
                ->where('id', '!=', $dup->primeiro_id)
                ->update(['cpf' => null]);
        }

        // 2. Adiciona o unique index. MySQL/MariaDB permitem múltiplos NULLs
        //    em coluna nullable mesmo com unique — clientes sem CPF coexistem.
        Schema::table('clientes', function (Blueprint $table) {
            $table->unique(['empresa_id', 'cpf'], 'clientes_empresa_cpf_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_empresa_cpf_unique');
        });
        // Não há como restaurar os CPFs zerados — backup de DB é a única opção.
    }
};
