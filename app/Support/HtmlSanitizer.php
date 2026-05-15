<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Allowlist de tags + neutralização de href maliciosa + remoção de event
     * handlers. Usado para campos administrativos que aceitam HTML rico e
     * são renderizados em páginas públicas (XSS aqui rouba sessão de
     * visitantes/admins).
     */
    public static function sanitize(string $html): string
    {
        $tagsPermitidas = '<h1><h2><h3><h4><h5><h6>'
            .'<p><br><hr><div><span>'
            .'<strong><em><b><i><u><s><small><sub><sup>'
            .'<ul><ol><li>'
            .'<blockquote><pre><code>'
            .'<a>'
            .'<table><thead><tbody><tfoot><tr><th><td>';

        $limpo = strip_tags($html, $tagsPermitidas);

        $limpo = preg_replace_callback(
            '/href\s*=\s*("|\')([^"\']*)\1/i',
            function ($m) {
                $url = trim($m[2]);
                if (preg_match('/^\s*(javascript|vbscript|data):/i', $url)) {
                    return 'href="#"';
                }
                return $m[0];
            },
            $limpo
        );

        $limpo = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $limpo);

        return $limpo;
    }
}
