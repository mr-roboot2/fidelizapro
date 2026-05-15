<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Criptografa em-place credenciais sensíveis da tabela `empresas`:
 * pdv_secret, whatsapp_api_token, whatsapp_webhook_verify_token.
 *
 * Padrão idêntico ao migration 000002 que fez o mesmo em
 * configuracoes_sistema. Tenta decryptar primeiro; se falhar, está em
 * plain → criptografa. Aumenta o tamanho das colunas pra caber o cifrado.
 *
 * Importante: usa `DB::statement('ALTER TABLE ... MODIFY ...')` ao invés de
 * `Schema::table->change()` porque a sintaxe Schema/Blueprint depende do
 * driver/versão do Laravel e, em produção, silenciou o ALTER em algumas
 * combinações (rodava o UPDATE sem ter alargado a coluna →
 * "Data too long for column"). DDL nativo do MySQL sempre commita antes do
 * próximo statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresas')) {
            return;
        }

        $colunas = [
            'pdv_secret',
            'whatsapp_api_token',
            'whatsapp_webhook_verify_token',
        ];

        // 1) Alarga as colunas para 500 chars via DDL nativo. MySQL faz
        //    auto-commit em DDL — quando esta linha retorna, o ALTER já
        //    está visível para o próximo statement.
        foreach ($colunas as $col) {
            if (!Schema::hasColumn('empresas', $col)) {
                continue;
            }
            // Re-execução idempotente: se a coluna já tem >=500, o MODIFY
            // ainda funciona (no-op efetivo). MySQL não reclama de MODIFY
            // pra mesmo tipo/tamanho.
            $sql = "ALTER TABLE `empresas` MODIFY COLUMN `{$col}` VARCHAR(500) NULL";
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Loga e segue — alguns MySQL antigos podem rejeitar MODIFY
                // se houver índice fulltext incompatível. Cripto abaixo
                // ainda tenta e vai falhar com mensagem clara se for o caso.
                logger()->warning("[migration encrypt empresas] ALTER falhou em {$col}: ".$e->getMessage());
            }
        }

        // 2) Cripto em-place. Para cada linha, decryptString primeiro:
        //    se passar, está cifrado (ignora). Se lançar, é plain → cifra.
        $cols = array_filter($colunas, fn ($c) => Schema::hasColumn('empresas', $c));
        if (empty($cols)) {
            return;
        }

        $linhas = DB::table('empresas')->select(array_merge(['id'], $cols))->get();
        foreach ($linhas as $linha) {
            $update = [];
            foreach ($cols as $col) {
                $valor = $linha->{$col} ?? null;
                if (empty($valor)) continue;
                try {
                    Crypt::decryptString($valor);
                    // já cifrado, ignora
                } catch (\Throwable $e) {
                    $update[$col] = Crypt::encryptString($valor);
                }
            }
            if (!empty($update)) {
                DB::table('empresas')->where('id', $linha->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Não há rollback seguro: voltar para plain expõe segredos.
    }
};
