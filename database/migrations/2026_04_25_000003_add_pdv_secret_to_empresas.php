<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('pdv_secret', 64)->nullable()->after('ativo')->index();
        });

        // Gera secret para empresas existentes
        DB::table('empresas')->whereNull('pdv_secret')->get()->each(function ($emp) {
            DB::table('empresas')->where('id', $emp->id)
                ->update(['pdv_secret' => 'sk_'.Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('pdv_secret');
        });
    }
};
