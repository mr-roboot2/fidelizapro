<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->decimal('preco_mensal', 10, 2)->default(0);
            $table->integer('limite_clientes')->nullable();         // null = ilimitado
            $table->integer('limite_compras_mes')->nullable();
            $table->integer('limite_recompensas')->nullable();
            $table->integer('limite_parceiros')->nullable();
            $table->integer('limite_users')->nullable();
            $table->integer('limite_campanhas_mes')->nullable();
            $table->boolean('whatsapp_ilimitado')->default(false);
            $table->boolean('automacoes_disponivel')->default(true);
            $table->boolean('parceiros_disponivel')->default(true);
            $table->boolean('white_label_disponivel')->default(false);
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('plano_id')->nullable()->after('ativo')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['plano_id']);
            $table->dropColumn('plano_id');
        });
        Schema::dropIfExists('planos');
    }
};
