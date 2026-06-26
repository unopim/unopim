<?php

use Webkul\Webhook\Validators\SafeWebhookUrl;

it('accepts a normal public https URL', function () {
    // Public IP literal — bypasses DNS so the test is deterministic in
    // network-restricted CI. 1.1.1.1 is in public unicast space.
    $result = SafeWebhookUrl::validate('https://1.1.1.1/inbound');

    expect($result['valid'])->toBeTrue();
    expect($result['reason'])->toBe('');
    expect($result['ip'])->toBe('1.1.1.1');
});

it('pins the resolved IP in httpOptions to close DNS-rebinding TOCTOU', function () {
    $options = SafeWebhookUrl::httpOptions('https://1.1.1.1/inbound');

    expect($options['allow_redirects'])->toBeFalse();
    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['1.1.1.1:443:1.1.1.1']);
});

it('omits the pin and falls back to redirect-disable when validation fails', function () {
    $options = SafeWebhookUrl::httpOptions('http://127.0.0.1/hook');

    expect($options['allow_redirects'])->toBeFalse();
    expect($options)->not->toHaveKey('curl');
});

it('rejects loopback IPv4 literal', function () {
    $result = SafeWebhookUrl::validate('http://127.0.0.1:9999/probe');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects 0.0.0.0 unspecified address', function () {
    $result = SafeWebhookUrl::validate('http://0.0.0.0:8080/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects IPv6 loopback literal', function () {
    $result = SafeWebhookUrl::validate('http://[::1]:8080/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects AWS cloud metadata IP 169.254.169.254', function () {
    $result = SafeWebhookUrl::validate('http://169.254.169.254/latest/meta-data/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
    expect($result['ip'])->toBe('169.254.169.254');
});

it('rejects IPv4 RFC1918 private range 10.0.0.0/8', function () {
    $result = SafeWebhookUrl::validate('http://10.0.0.1/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects IPv4 RFC1918 private range 172.16.0.0/12', function () {
    $result = SafeWebhookUrl::validate('http://172.16.5.10/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects IPv4 RFC1918 private range 192.168.0.0/16', function () {
    $result = SafeWebhookUrl::validate('http://192.168.1.1/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects multicast range 224.0.0.0/4', function () {
    $result = SafeWebhookUrl::validate('http://224.0.0.1/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('restricted_ip');
});

it('rejects file:// scheme', function () {
    $result = SafeWebhookUrl::validate('file:///etc/passwd');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('invalid_scheme');
});

it('rejects gopher:// scheme', function () {
    $result = SafeWebhookUrl::validate('gopher://attacker.com/');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('invalid_scheme');
});

it('rejects ftp:// scheme', function () {
    $result = SafeWebhookUrl::validate('ftp://attacker.com/payload');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('invalid_scheme');
});

it('rejects empty url', function () {
    $result = SafeWebhookUrl::validate('');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('empty_url');
});

it('rejects null url', function () {
    $result = SafeWebhookUrl::validate(null);

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('empty_url');
});

it('rejects garbage url with no host', function () {
    $result = SafeWebhookUrl::validate('http://');

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('invalid_url');
});

it('allows loopback only when WEBHOOK_ALLOW_LOOPBACK opt-in is set', function () {
    putenv('WEBHOOK_ALLOW_LOOPBACK=true');

    try {
        // Loopback now allowed.
        $loopback = SafeWebhookUrl::validate('http://127.0.0.1:9999/probe');
        expect($loopback['valid'])->toBeTrue();

        $loopback6 = SafeWebhookUrl::validate('http://[::1]:9999/probe');
        expect($loopback6['valid'])->toBeTrue();

        // Private / link-local / multicast must STILL be blocked.
        $private = SafeWebhookUrl::validate('http://10.0.0.1/');
        expect($private['valid'])->toBeFalse();
        expect($private['reason'])->toBe('restricted_ip');

        $metadata = SafeWebhookUrl::validate('http://169.254.169.254/');
        expect($metadata['valid'])->toBeFalse();
        expect($metadata['reason'])->toBe('restricted_ip');

        $multicast = SafeWebhookUrl::validate('http://224.0.0.1/');
        expect($multicast['valid'])->toBeFalse();
        expect($multicast['reason'])->toBe('restricted_ip');
    } finally {
        putenv('WEBHOOK_ALLOW_LOOPBACK');
    }
});
