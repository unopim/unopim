<?php

namespace Webkul\Webhook\Validators;

/**
 * Validate that a webhook URL is safe to call from the server side.
 *
 * Rejects URLs that resolve (A + AAAA) to loopback / private / link-local /
 * multicast / reserved address space — including the cloud metadata
 * 169.254.169.254 covered by FILTER_FLAG_NO_RES_RANGE.
 *
 * Applied at every Http::post() sink that takes user-supplied URLs:
 *  - WebhookSettingsController::store + testWebhookUrl
 *  - WebhookService::sendDataToWebhook / sendCreatedToWebhook / sendBatch
 *
 * DNS-rebinding (TOCTOU) defense: callers pair validate() with
 * httpOptions($url), which returns a CURLOPT_RESOLVE entry pinning the
 * hostname to the validated IP so the connection cannot re-resolve to an
 * internal address between check and use. HTTP redirects are also disabled.
 *
 * Test-env opt-in: setting WEBHOOK_ALLOW_LOOPBACK=true (default false) lets
 * the loopback range pass so Playwright E2E can spin up a localhost catcher
 * to verify end-to-end webhook delivery. Private / link-local / multicast /
 * reserved are still blocked even with the flag on.
 */
class SafeWebhookUrl
{
    /**
     * @return array{valid: bool, reason: string, ip?: string}
     */
    public static function validate(?string $url): array
    {
        if ($url === null || $url === '') {
            return ['valid' => false, 'reason' => 'empty_url'];
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return ['valid' => false, 'reason' => 'invalid_url'];
        }

        if (empty($parts['scheme']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return ['valid' => false, 'reason' => 'invalid_scheme'];
        }

        if (empty($parts['host'])) {
            return ['valid' => false, 'reason' => 'invalid_url'];
        }

        $host = $parts['host'];

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $ips = self::resolveHost($host);

            if ($ips === []) {
                return ['valid' => false, 'reason' => 'dns_lookup_failed'];
            }
        }

        foreach ($ips as $ip) {
            if (! self::isAllowedIp($ip)) {
                return ['valid' => false, 'reason' => 'restricted_ip', 'ip' => $ip];
            }
        }

        return ['valid' => true, 'reason' => '', 'ip' => $ips[0]];
    }

    /**
     * Build the Http::withOptions() payload used at every dispatch sink.
     *
     * Returns an array always carrying `allow_redirects: false` and, when the
     * URL resolves to a validated public IP, a CURLOPT_RESOLVE entry pinning
     * `host:port` to that IP. Pinning closes the DNS-rebinding TOCTOU window:
     * the connection bypasses the resolver and goes straight to the IP that
     * was already checked against the reserved-range allowlist. SNI / Host
     * header still carry the original hostname, so TLS certificate validation
     * is unaffected.
     *
     * Returns redirect-blocking options only when the URL fails validation —
     * the dispatch site MUST guard with validate() first, but this fallback
     * stops the request from following 30x chains in the failure path.
     *
     * @return array<string, mixed>
     */
    public static function httpOptions(string $url): array
    {
        $options = ['allow_redirects' => false];

        $check = self::validate($url);

        if (! ($check['valid'] ?? false) || empty($check['ip'])) {
            return $options;
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? '';

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $port = $parts['port'] ?? (strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80);

        // CURLOPT_RESOLVE wants the literal IPv6 wrapped in brackets so the
        // resolver entry parses correctly.
        $ip = $check['ip'];
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = "[{$ip}]";
        }

        $options['curl'] = [
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
        ];

        return $options;
    }

    /**
     * Resolve hostname to all A + AAAA records.
     *
     * @return array<int, string>
     */
    protected static function resolveHost(string $host): array
    {
        $ipv4 = @gethostbynamel($host) ?: [];

        $ipv6 = [];
        $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];

        foreach ($aaaa as $record) {
            if (! empty($record['ipv6'])) {
                $ipv6[] = $record['ipv6'];
            }
        }

        return array_values(array_unique(array_merge($ipv4, $ipv6)));
    }

    /**
     * Accept only public unicast IPs.
     *
     * filter_var with FILTER_FLAG_NO_PRIV_RANGE blocks RFC1918 (10/8,
     * 172.16/12, 192.168/16) and IPv6 unique-local (fc00::/7).
     * FILTER_FLAG_NO_RES_RANGE blocks 0.0.0.0/8, 127.0.0.0/8 (loopback),
     * 169.254.0.0/16 (link-local incl. 169.254.169.254 cloud-metadata),
     * 192.0.0.0/24, 192.0.2.0/24, 198.18.0.0/15, 198.51.100.0/24,
     * 203.0.113.0/24, 240.0.0.0/4 (reserved), and IPv6 ::/128, ::1/128,
     * ::ffff:0:0/96, fe80::/10. Multicast (IPv4 224/4, IPv6 ff00::/8) is
     * NOT covered by the flag, so it is checked separately via inet_pton.
     */
    protected static function isAllowedIp(string $ip): bool
    {
        // Loopback opt-in for Playwright E2E delivery test (explicit
        // allowlist, off in production by default). getenv() covers the
        // putenv() path used by tests; config covers .env deployments.
        $loopbackAllowed = filter_var(
            getenv('WEBHOOK_ALLOW_LOOPBACK') ?: config('webhook.allow_loopback', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $bin = @inet_pton($ip);

        if ($bin === false) {
            return false;
        }

        // IPv4 multicast 224.0.0.0/4 — first nibble 0xe (0xe0..0xef).
        if (strlen($bin) === 4 && (ord($bin[0]) & 0xF0) === 0xE0) {
            return false;
        }

        // IPv6 multicast ff00::/8 — first byte 0xff.
        if (strlen($bin) === 16 && ord($bin[0]) === 0xFF) {
            return false;
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if ($loopbackAllowed) {
            // Re-allow ONLY the loopback ranges (127/8, ::1) while keeping
            // every other reserved/private/link-local/multicast block intact.
            if (strlen($bin) === 4 && ord($bin[0]) === 127) {
                return true;
            }
            if (strlen($bin) === 16 && $ip === '::1') {
                return true;
            }
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
    }
}
