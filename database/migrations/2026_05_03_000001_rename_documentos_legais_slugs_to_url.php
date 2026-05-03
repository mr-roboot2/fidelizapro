<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renomeia os slugs dos documentos legais pra serem a URL completa.
 * 'privacidade' -> 'politica-privacidade'
 * 'termos'      -> 'termos-de-uso'
 *
 * Permite que o super admin edite o slug livremente e a URL pública
 * passe a ser exatamente /{slug}.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('documentos_legais')->where('slug', 'privacidade')->update(['slug' => 'politica-privacidade']);
        DB::table('documentos_legais')->where('slug', 'termos')->update(['slug' => 'termos-de-uso']);
    }

    public function down(): void
    {
        DB::table('documentos_legais')->where('slug', 'politica-privacidade')->update(['slug' => 'privacidade']);
        DB::table('documentos_legais')->where('slug', 'termos-de-uso')->update(['slug' => 'termos']);
    }
};
