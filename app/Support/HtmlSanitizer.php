<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Allowlist de tags + neutralização de href maliciosa + remoção de event
     * handlers. Usado para campos administrativos que aceitam HTML rico e
     * são renderizados em páginas públicas (XSS aqui rouba sessão de
     * visitantes/admins).
     *
     * Vetores fechados nesta versão (após pentest 2026-05-15):
     *   - <a/onclick=...> (separador `/` em vez de espaço)
     *   - href=javascript:... sem aspas
     *   - href="&#106;avascript:..." (entity-encoded scheme)
     *   - Bypass via newline/tab antes do event handler
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

        // 1) Decodifica entities ANTES de validar href — atacante usava
        // `href="&#106;avascript:..."` que browser decodifica como
        // `javascript:` mas a regex de string não casava.
        $limpoNormalizado = html_entity_decode($limpo, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2) Bloqueia esquemas perigosos em href, com ou sem aspas, e
        // case/whitespace insensitive. Match cobre:
        //   href="javascript:..."  href='javascript:...'  href=javascript:...
        //   (e variantes vbscript:/data:/file:)
        $limpoNormalizado = preg_replace_callback(
            '/href\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            function ($m) {
                // preg_replace_callback retorna '' (não null) para grupos
                // não capturados → não dá pra usar `??`. Escolhe o primeiro
                // grupo não-vazio.
                $url = '';
                foreach ([1, 2, 3] as $i) {
                    if (!empty($m[$i])) { $url = $m[$i]; break; }
                }
                $url = trim($url);
                // Normaliza pra detectar javascript:/vbscript:/data:/file:
                // ignorando whitespace/tab/newline internos (browser ignora).
                $limpoUrl = preg_replace('/[\s\x00-\x1f]+/', '', strtolower($url));
                if (preg_match('/^(javascript|vbscript|data|file):/i', $limpoUrl)) {
                    return 'href="#"';
                }
                // Reescreve sempre com aspas duplas — atacante perdia o
                // benefício do unquoted href.
                return 'href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"';
            },
            $limpoNormalizado
        );

        // 3) Remove event handlers inline. Aceita QUALQUER caractere
        // whitespace OU `/` antes do `on...=` — browsers HTML5 tratam
        // `/` como separador equivalente a espaço (`<a/onclick=...>`).
        // Também aceita aspas, sem aspas, ou valor sem delimitador.
        $limpoNormalizado = preg_replace(
            '/[\s\/]on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $limpoNormalizado
        );

        // 4) Remove atributos style — CSS pode exfiltrar via background-image
        // url() e selectors. Allowlist do sanitizer não precisa de style.
        $limpoNormalizado = preg_replace(
            '/[\s\/]style\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $limpoNormalizado
        );

        return $limpoNormalizado;
    }
}
