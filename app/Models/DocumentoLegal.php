<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoLegal extends Model
{
    protected $table = 'documentos_legais';

    protected $fillable = ['slug', 'titulo', 'conteudo'];

    public static function porSlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
