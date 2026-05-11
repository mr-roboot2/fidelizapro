<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('logo_bg_color', 7)->default('#000000')->after('logo');
            $table->unsignedTinyInteger('logo_scale')->default(100)->after('logo_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['logo_bg_color', 'logo_scale']);
        });
    }
};
