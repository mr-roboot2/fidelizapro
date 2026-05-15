<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aumenta o tamanho das colunas de token e cripto­grafa em-place valores
 * plain-text que passaram a ter cast 'encrypted' no model ConfiguracaoSistema:
 * pix_webhook_token, whatsapp_api_token, whatsapp_client_token,
 * whatsapp_webhook_verify_token. Tenta decryptar primeiro; se falhar, está em
 * plain → criptografa. O valor cifrado pelo Laravel é maior que o original,
 * por isso o ALTER de tamanho precede a cifragem.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes_sistema')) {
            return;
        }

        $colunas = [
            'whatsapp_api_token',
            'whatsapp_client_token',
            'whatsapp_webhook_verify_token',
            'pix_webhook_token',
        ];

        // Aumenta as colunas existentes pra caber o valor cifrado
        Schema::table('configuracoes_sistema', function (Blueprint $table) use ($colunas) {
            foreach ($colunas as $col) {
                if (Schema::hasColumn('configuracoes_sistema', $col)) {
                    $table->string($col, 500)->nullable()->change();
                }
            }
        });

        $cols = array_filter($colunas, fn ($c) => Schema::hasColumn('configuracoes_sistema', $c));
        if (empty($cols)) {
            return;
        }

        $linhas = DB::table('configuracoes_sistema')->select(array_merge(['id'], $cols))->get();
        foreach ($linhas as $linha) {
            $update = [];
            foreach ($cols as $col) {
                $valor = $linha->{$col} ?? null;
                if (empty($valor)) continue;
                try {
                    Crypt::decryptString($valor);
                    // já criptografado, ignora
                } catch (\Throwable $e) {
                    $update[$col] = Crypt::encryptString($valor);
                }
            }
            if (!empty($update)) {
                DB::table('configuracoes_sistema')->where('id', $linha->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Não há rollback seguro: voltar para plain expõe segredos.
    }
};
