<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique em `cobrancas.gateway_charge_id`. Sem isso, dois registros
 * com o mesmo id podiam coexistir (ex: cobrança regerada com mesmo charge_id
 * do gateway) e o webhook `where('gateway_charge_id', X)->first()` pegava
 * uma linha não-determinística, marcando a cobrança errada como paga.
 *
 * Permite múltiplas linhas com `gateway_charge_id = NULL` (cobranças que
 * ainda não foram enviadas ao gateway) — comportamento padrão do unique
 * em MySQL quando a coluna é nullable.
 *
 * Antes de criar o índice, limpa duplicatas: mantém a mais recente (id maior)
 * pra cada gateway_charge_id repetido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cobrancas')) {
            return;
        }

        // Deduplicação preventiva: pra cada gateway_charge_id repetido,
        // zera o gateway_charge_id de todas EXCETO a mais recente. Não
        // apaga linha — atacante pode ter pagado e o histórico precisa
        // existir, só o link pro gateway é que vira null nos "duplicados".
        $duplicados = DB::table('cobrancas')
            ->select('gateway_charge_id', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('gateway_charge_id')
            ->where('gateway_charge_id', '!=', '')
            ->groupBy('gateway_charge_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicados as $d) {
            DB::table('cobrancas')
                ->where('gateway_charge_id', $d->gateway_charge_id)
                ->where('id', '!=', $d->keep_id)
                ->update(['gateway_charge_id' => null]);
        }

        Schema::table('cobrancas', function (Blueprint $table) {
            $table->unique('gateway_charge_id', 'cobrancas_gateway_charge_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cobrancas', function (Blueprint $table) {
            $table->dropUnique('cobrancas_gateway_charge_id_unique');
        });
    }
};
