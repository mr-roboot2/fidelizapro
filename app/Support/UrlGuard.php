<?php

namespace App\Support;

/**
 * Anti-SSRF: validação de URLs configuradas por admins (whatsapp_api_url,
 * webhooks customizados, etc.) pra impedir que apontem para serviços
 * internos do servidor / metadata cloud / loopback.
 *
 * Cenário: super admin (ou conta dele comprometida) coloca como URL do
 * gateway WhatsApp `http://169.254.169.254/latest/meta-data/iam/security-credentials/`.
 * Quando a aplicação tentar enviar uma mensagem, a chamada HTTP sai com
 * o token de API como header, e a resposta (cloud creds) cai no log
 * de erro. Equivalente a SSRF arbitrária dentro da rede do servidor.
 *
 * Uso típico:
 *
 *   if (UrlGuard::isInternal($url)) {
 *       return back()->withErrors(['whatsapp_api_url' => 'URL não permitida.']);
 *   }
 *
 * Ou via Validation Rule:
 *
 *   'whatsapp_api_url' => ['nullable', 'url', new \App\Rules\NaoInterno()]
 */
class UrlGuard
{
    /**
     * Hostnames literais explicitamente bloqueados (case-insensitive).
     */
    protected const HOSTS_BLOQUEADOS = [
        'localhost',
        'metadata.google.internal',
        'metadata.azure.com',
    ];

    /**
     * Sufixos de hostname considerados internos.
     */
    protected const SUFFIXES_BLOQUEADOS = [
        '.local',
        '.internal',
        '.localdomain',
    ];

    /**
     * Retorna true se a URL deve ser BLOQUEADA (interna/inválida/scheme errado).
     * URLs vazias/null retornam false (deixa o `required|url` do validator
     * tratar — não é trabalho desta classe).
     */
    public static function isInternal(?string $url): bool
    {
        if (empty($url)) return false;

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) return true;

        // Só HTTP/HTTPS. Bloqueia file://, gopher://, ftp://, ldap:// etc.
        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) return true;

        $host = strtolower($parts['host']);

        // Hostnames literais
        if (in_array($host, self::HOSTS_BLOQUEADOS, true)) return true;

        // Sufixos suspeitos
        foreach (self::SUFFIXES_BLOQUEADOS as $sufixo) {
            if (str_ends_with($host, $sufixo)) return true;
        }

        // Resolve para IP. Note: atacante pode usar DNS rebinding —
        // resolver agora não garante que o IP seja o mesmo no momento do
        // request HTTP real. Mitigação real exige forçar IP fixo na hora
        // da request (não trivial com Guzzle padrão). Esta camada já
        // bloqueia 99% dos casos triviais (hostname interno conhecido).
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : @gethostbyname($host);

        // Se gethostbyname falhou, ele devolve o próprio hostname.
        // Considera inválido por precaução.
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;

        return self::isPrivateIp($ip);
    }

    /**
     * IPv4/IPv6 privados, loopback, link-local, multicast, reservados.
     * Usa filter_var com flags conhecidas; cobre as faixas críticas:
     *   - 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16 (privados)
     *   - 127.0.0.0/8 (loopback IPv4)
     *   - 169.254.0.0/16 (link-local IPv4 — inclui cloud metadata)
     *   - ::1 (loopback IPv6)
     *   - fc00::/7 (ULA), fe80::/10 (link-local IPv6)
     */
    public static function isPrivateIp(string $ip): bool
    {
        // FILTER_FLAG_NO_PRIV_RANGE | NO_RES_RANGE retorna FALSE para
        // endereços privados/reservados — invertemos a lógica: se passar
        // SEM esses flags mas falhar COM eles, é privado.
        $publico = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        return $publico === false;
    }
}
