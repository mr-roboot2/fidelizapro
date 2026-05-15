<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Criptografa em-place valores plain-text que passaram a ter cast 'encrypted'
 * no model ConfiguracaoSistema: whatsapp_api_token, whatsapp_client_token,
 * whatsapp_webhook_verify_token e pix_webhook_token. Tenta decryptar primeiro;
 * se falhar, está em plain → criptografa.
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
