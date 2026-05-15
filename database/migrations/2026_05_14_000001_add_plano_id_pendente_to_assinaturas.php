<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->foreignId('plano_id_pendente')->nullable()->after('plano_id')
                  ->constrained('planos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plano_id_pendente');
        });
    }
};
