<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sorteio_bilhetes', function (Blueprint $table) {
            $table->unsignedInteger('numero')->nullable()->after('cliente_id');
        });

        // Backfill: pra cada sorteio existente, numera os bilhetes em ordem de criação
        foreach (DB::table('sorteios')->pluck('id') as $sorteioId) {
            $i = 1;
            foreach (DB::table('sorteio_bilhetes')->where('sorteio_id', $sorteioId)->orderBy('id')->pluck('id') as $bilheteId) {
                DB::table('sorteio_bilhetes')->where('id', $bilheteId)->update(['numero' => $i++]);
            }
        }

        Schema::table('sorteio_bilhetes', function (Blueprint $table) {
            // Único por sorteio — garante consistência mesmo com race condition
            $table->unique(['sorteio_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::table('sorteio_bilhetes', function (Blueprint $table) {
            $table->dropUnique(['sorteio_id', 'numero']);
            $table->dropColumn('numero');
        });
    }
};
