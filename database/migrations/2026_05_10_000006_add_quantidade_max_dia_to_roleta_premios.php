<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roleta_premios', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantidade_max_dia')->nullable()->after('peso');
        });
    }

    public function down(): void
    {
        Schema::table('roleta_premios', function (Blueprint $table) {
            $table->dropColumn('quantidade_max_dia');
        });
    }
};
