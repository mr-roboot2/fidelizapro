<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->unsignedSmallInteger('trial_dias_padrao')->default(7)->after('cobranca_avisos_depois');
            $table->foreignId('plano_padrao_id')->nullable()->after('trial_dias_padrao')
                  ->constrained('planos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropForeign(['plano_padrao_id']);
            $table->dropColumn(['trial_dias_padrao', 'plano_padrao_id']);
        });
    }
};
