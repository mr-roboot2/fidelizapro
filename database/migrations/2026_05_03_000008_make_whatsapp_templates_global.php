<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Templates de WhatsApp passam a ser globais (uma config por evento,
 * usada por todas as empresas). Coerente com WABA única no super admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Deduplica: mantém o registro mais recente por evento e descarta o resto
        $eventos = DB::table('whatsapp_templates')->distinct()->pluck('evento');
        foreach ($eventos as $evento) {
            $manterId = DB::table('whatsapp_templates')
                ->where('evento', $evento)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('whatsapp_templates')
                ->where('evento', $evento)
                ->where('id', '!=', $manterId)
                ->delete();
        }

        // Ordem importa: FK depende do índice, índice composto depende da coluna
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'evento']);
        });
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
        });
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->unique('evento');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropUnique(['evento']);
            $table->foreignId('empresa_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['empresa_id', 'evento']);
        });
    }
};
