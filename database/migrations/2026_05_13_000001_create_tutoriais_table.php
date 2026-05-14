<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutoriais', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('tipo_video', ['url', 'upload'])->default('url');
            $table->string('video_url', 500)->nullable();
            $table->string('video_arquivo', 500)->nullable();
            $table->string('duracao', 20)->nullable();
            $table->unsignedInteger('ordem')->default(0)->index();
            $table->boolean('publicado')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutoriais');
    }
};
