<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trial_ate é gravado pelo AssinaturaService::criar/EmpresaObserver com
 * `now()->addDays(N)` (datetime com hora). A coluna era DATE → MySQL
 * truncava pra 00:00:00 do dia, fazendo o trial expirar até 24h antes do
 * prometido (cliente cria conta às 14h do dia X, trial 7 dias → expira
 * no dia X+7 às 00:00 em vez de X+7 às 23:59).
 *
 * Cast no model já foi mudado pra 'datetime'. Esta migration alinha o
 * schema. DATE → DATETIME é mudança sem perda de dados: o MySQL converte
 * valores DATE existentes adicionando 00:00:00.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->dateTime('trial_ate')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->date('trial_ate')->nullable()->change();
        });
    }
};
