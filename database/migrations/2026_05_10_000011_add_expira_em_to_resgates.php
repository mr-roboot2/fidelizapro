<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resgates', function (Blueprint $table) {
            $table->timestamp('expira_em')->nullable()->after('aprovado_em');
            $table->index(['empresa_id', 'expira_em']);
        });
    }

    public function down(): void
    {
        Schema::table('resgates', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'expira_em']);
            $table->dropColumn('expira_em');
        });
    }
};
