<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->integer('dias_liberar_cashback')->default(0)->after('cashback_percentual');
        });

        Schema::table('movimentos_cashback', function (Blueprint $table) {
            $table->dateTime('liberado_em')->nullable()->after('descricao');
            $table->boolean('processado')->default(false)->after('liberado_em');
            $table->index(['processado', 'liberado_em']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('cashback_pendente', 12, 2)->default(0)->after('cashback_atual');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('dias_liberar_cashback');
        });
        Schema::table('movimentos_cashback', function (Blueprint $table) {
            $table->dropColumn('liberado_em');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cashback_pendente');
        });
    }
};
