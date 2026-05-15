<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Schema::table('empresas', function (Blueprint $table) use ($colunas) {
            foreach ($colunas as $col) {
                if (Schema::hasColumn('empresas', $col)) {
                    $table->string($col, 500)->nullable()->change();
                }
            }
        });

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
