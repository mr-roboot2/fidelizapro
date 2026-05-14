<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tutorial extends Model
{
    protected $table = 'tutoriais';

    protected $fillable = [
        'titulo',
        'descricao',
        'tipo_video',
        'video_url',
        'video_arquivo',
        'duracao',
        'ordem',
        'publicado',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'ordem'     => 'integer',
    ];

    public function scopePublicados($query)
    {
        return $query->where('publicado', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('ordem')->orderBy('id');
    }

    /**
     * Retorna a URL absoluta do arquivo (upload) ou a URL externa direta.
     */
    public function videoSrc(): ?string
    {
        if ($this->tipo_video === 'upload' && $this->video_arquivo) {
            return Storage::disk('public')->url($this->video_arquivo);
        }
        return $this->video_url ?: null;
    }

    /**
     * URL de embed pronta pra <iframe> (YouTube/Vimeo) — ou null se não for embedável.
     * Upload não usa iframe; é servido em <video>.
     */
    public function embedUrl(): ?string
    {
        if ($this->tipo_video !== 'url' || !$this->video_url) {
            return null;
        }

        $url = trim($this->video_url);

        // YouTube — formatos comuns: youtu.be/ID, youtube.com/watch?v=ID, youtube.com/embed/ID, shorts/ID
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Vimeo — vimeo.com/ID ou player.vimeo.com/video/ID
        if (preg_match('~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }

    public function isUpload(): bool
    {
        return $this->tipo_video === 'upload' && !empty($this->video_arquivo);
    }
}
