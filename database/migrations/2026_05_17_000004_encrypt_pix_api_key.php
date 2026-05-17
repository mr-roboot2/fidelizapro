<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `pix_api_key` recebeu cast 'encrypted' em ConfiguracaoSistema mas a
 * migration 2026_05_15_000002 não incluiu a coluna na cripto in-place.
 * Resultado: valores legados (cadastrados antes do cast) ficam em plain
 * no DB, e qualquer leitura via Model dispara DecryptException ao
 * tentar decifrar texto não-cifrado — quebra o painel pra empresas
 * que tinham config PIX antes da rodada de cripto.
 *
 * Esta migration:
 *   1) ALTER coluna pra TEXT (valor cifrado pode estourar VARCHAR curto)
 *   2) Tenta decryptString — se sucesso, já está cifrado (nada a fazer)
 *   3) Se falha, considera plain e cifra in-place
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes_sistema')) return;
        if (!Schema::hasColumn('configuracoes_sistema', 'pix_api_key')) return;

        // 1) Expande coluna pra TEXT antes de cifrar (valor encryptado é maior).
        //    Schema::change pode não detectar mudança de tipo em alguns drivers —
        //    usa DDL direto pra garantir.
        try {
            DB::statement("ALTER TABLE configuracoes_sistema MODIFY COLUMN pix_api_key TEXT NULL");
        } catch (\Throwable $e) {
            // tolerante: se já é TEXT, o ALTER é no-op em alguns DBs
        }

        // 2) Itera linhas e cifra valores ainda plain.
        $linhas = DB::table('configuracoes_sistema')->select('id', 'pix_api_key')->get();
        foreach ($linhas as $linha) {
            $valor = $linha->pix_api_key ?? null;
            if (empty($valor)) continue;

            try {
                Crypt::decryptString($valor);
                // já cifrado, nada a fazer
            } catch (\Throwable $e) {
                $cifrado = Crypt::encryptString($valor);
                DB::table('configuracoes_sistema')
                    ->where('id', $linha->id)
                    ->update(['pix_api_key' => $cifrado]);
            }
        }
    }

    public function down(): void
    {
        // Não há rollback seguro: voltar pra plain expõe credenciais.
    }
};
