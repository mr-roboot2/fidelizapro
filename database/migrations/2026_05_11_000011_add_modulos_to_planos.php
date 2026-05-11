<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            $table->json('modulos')->nullable()->after('white_label_disponivel');
        });

        // Backfill: traduz os flags antigos pros novos módulos.
        // Planos com white_label viram "plano max" com tudo; demais ganham
        // o mínimo (roleta + sorteio + métricas — features básicas novas).
        foreach (DB::table('planos')->get() as $plano) {
            $modulos = ['roleta', 'sorteio', 'metricas', 'indicacoes'];
            if ($plano->automacoes_disponivel) $modulos[] = 'automacoes';
            if ($plano->whatsapp_ilimitado) $modulos[] = 'whatsapp';
            if ($plano->parceiros_disponivel) $modulos[] = 'parceiros';
            if ($plano->white_label_disponivel) {
                $modulos[] = 'white_label';
                $modulos[] = 'antifraude';
                $modulos[] = 'campanhas';
            }
            DB::table('planos')->where('id', $plano->id)->update(['modulos' => json_encode(array_values(array_unique($modulos)))]);
        }
    }

    public function down(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            $table->dropColumn('modulos');
        });
    }
};
