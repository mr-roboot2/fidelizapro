<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Criptografa em-place credenciais sensíveis da tabela `empresas`:
 * pdv_secret, whatsapp_api_token, whatsapp_webhook_verify_token.
 *
 * Estratégia em 3 fases pra ser robusta em qualquer versão de MySQL:
 *   1. Diagnóstico — captura tipo atual de cada coluna (log).
 *   2. ALTER — usa DDL nativo (`MODIFY COLUMN ... TEXT NULL`). Escolhe TEXT
 *      ao invés de VARCHAR(500) porque TEXT é menos restritivo:
 *        - não conta no row size limit (65535 bytes) do MySQL,
 *        - aceita conteúdo cifrado de qualquer tamanho,
 *        - sobrevive a qualquer charset (utf8mb4 multiplica bytes).
 *      Sem try/catch silencioso — se o ALTER falhar, a migration QUEBRA
 *      visivelmente com o erro real do MySQL.
 *   3. Verificação + cifragem em-place. Antes de tentar o UPDATE, valida
 *      que a coluna comporta o payload cifrado. Cripto é idempotente
 *      (decryptString primeiro; só cifra o que está plain).
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

        // 1) Diagnóstico — registra no log o tipo atual de cada coluna pra
        //    facilitar postmortem em caso de falha.
        $tiposAntes = [];
        foreach ($colunas as $col) {
            if (!Schema::hasColumn('empresas', $col)) continue;
            $info = DB::selectOne("SHOW COLUMNS FROM `empresas` LIKE ?", [$col]);
            $tiposAntes[$col] = $info?->Type ?? 'desconhecido';
        }
        logger()->info('[migration encrypt empresas] tipos antes do ALTER', $tiposAntes);

        // 2) ALTER pra TEXT. Sem try/catch — se algo bloquear o ALTER, a
        //    migration falha aqui com o erro nativo do MySQL (FK, index,
        //    permission). Mensagem fica acionável.
        foreach ($colunas as $col) {
            if (!Schema::hasColumn('empresas', $col)) continue;
            DB::statement("ALTER TABLE `empresas` MODIFY COLUMN `{$col}` TEXT NULL");
        }

        // 3) Confere que o ALTER realmente aplicou (paranoia — caso o driver
        //    do PHP esteja com cache antigo do schema). Se a coluna ainda
        //    estiver pequena, aborta com mensagem explícita ao invés de cair
        //    no "Data too long" críptico do UPDATE.
        foreach ($colunas as $col) {
            if (!Schema::hasColumn('empresas', $col)) continue;
            $info = DB::selectOne("SHOW COLUMNS FROM `empresas` LIKE ?", [$col]);
            $tipo = strtolower($info?->Type ?? '');
            $aceita = str_contains($tipo, 'text') || str_contains($tipo, 'mediumtext') || str_contains($tipo, 'longtext')
                || (preg_match('/varchar\((\d+)\)/', $tipo, $m) && (int) $m[1] >= 500);
            if (!$aceita) {
                throw new RuntimeException(
                    "ALTER de empresas.{$col} para TEXT não aplicou. "
                    ."Tipo atual: '{$tipo}'. Antes: '".($tiposAntes[$col] ?? '?')."'. "
                    ."Cheque manualmente: SHOW CREATE TABLE empresas; e veja se há FK/INDEX bloqueando."
                );
            }
        }

        // 4) Cripto idempotente em-place.
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
