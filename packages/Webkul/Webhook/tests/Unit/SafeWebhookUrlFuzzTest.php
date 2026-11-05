<?php

use Webkul\Webhook\Validators\SafeWebhookUrl;

/**
 * Randomised inputs against the SSRF guard. Each run picks a fresh seed; set
 * FUZZ_SEED to replay the one printed in a failure message.
 */
beforeEach(function () {
    $this->seed = (int) (getenv('FUZZ_SEED') ?: random_int(1, mt_getrandmax()));

    mt_srand($this->seed);
});

dataset('restricted ipv4 ranges', [
    'loopback 127/8'       => [[127, 127], [0, 255]],
    'unspecified 0/8'      => [[0, 0], [0, 255]],
    'private 10/8'         => [[10, 10], [0, 255]],
    'private 172.16/12'    => [[172, 172], [16, 31]],
    'private 192.168/16'   => [[192, 192], [168, 168]],
    'link-local 169.254'   => [[169, 169], [254, 254]],
    'multicast 224/4'      => [[224, 239], [0, 255]],
    'reserved 240/4'       => [[240, 255], [0, 255]],
]);

it('rejects every address in a restricted IPv4 range, in any notation', function (array $first, array $second) {
    for ($i = 0; $i < 60; $i++) {
        $octets = [mt_rand(...$first), mt_rand(...$second), mt_rand(0, 255), mt_rand(0, 255)];

        $host = match (mt_rand(0, 4)) {
            0 => implode('.', $octets),
            1 => (string) (($octets[0] << 24) | ($octets[1] << 16) | ($octets[2] << 8) | $octets[3]),
            2 => implode('.', array_map(fn (int $octet): string => '0x'.dechex($octet), $octets)),
            3 => implode('.', array_map(fn (int $octet): string => '0'.decoct($octet), $octets)),
            4 => '[::ffff:'.implode('.', $octets).']',
        };

        $url = (mt_rand(0, 1) ? 'http' : 'https').'://'.(mt_rand(0, 3) === 0 ? 'user:pass@' : '').$host.(mt_rand(0, 1) ? ':'.mt_rand(1, 65535) : '').'/hook';

        expect(SafeWebhookUrl::validate($url)['valid'])->toBeFalse("Accepted {$url} (FUZZ_SEED={$this->seed})");
        expect(SafeWebhookUrl::httpOptions($url))->not->toHaveKey('curl');
    }
})->with('restricted ipv4 ranges');

it('rejects every address in a restricted IPv6 range', function () {
    $prefixes = [
        'loopback'     => fn (): string => '::1',
        'link-local'   => fn (): string => sprintf('fe%x%x', mt_rand(8, 11), mt_rand(0, 15)),
        'unique-local' => fn (): string => sprintf('f%x%02x', mt_rand(12, 13), mt_rand(0, 255)),
        'multicast'    => fn (): string => sprintf('ff%02x', mt_rand(0, 255)),
    ];

    for ($i = 0; $i < 400; $i++) {
        $name = array_rand($prefixes);
        $prefix = $prefixes[$name]();

        $ip = $name === 'loopback'
            ? $prefix
            : $prefix.':'.implode(':', array_map(fn (): string => dechex(mt_rand(0, 0xFFFF)), range(1, 7)));

        $url = 'http://['.$ip.']/hook';

        expect(SafeWebhookUrl::validate($url)['valid'])->toBeFalse("Accepted {$url} (FUZZ_SEED={$this->seed})");
    }
});

it('rejects anything that is not an http or https URL without resolving it', function () {
    $alphabet = array_merge(range('a', 'z'), range('0', '9'), str_split(":/@[]?#%.-_+~\\ \t\n\0"));

    for ($i = 0; $i < 500; $i++) {
        $scheme = implode('', array_map(fn (): string => chr(mt_rand(97, 122)), range(1, mt_rand(1, 8))));
        $tail = implode('', array_map(fn (): string => $alphabet[array_rand($alphabet)], range(1, mt_rand(0, 40))));

        $url = mt_rand(0, 1) ? $scheme.'://'.$tail : $tail;

        if (preg_match('/^\s*https?:/i', $url)) {
            continue;
        }

        $result = SafeWebhookUrl::validate($url);

        expect($result['valid'])->toBeFalse("Accepted {$url} (FUZZ_SEED={$this->seed})")
            ->and($result['reason'])->toBeIn(['empty_url', 'invalid_url', 'invalid_scheme']);
    }
});

it('accepts public IPv4 literals and pins the connection to them', function () {
    $publicFirstOctets = [1, 8, 9, 13, 20, 34, 52, 64, 81, 104, 151, 185, 208];

    for ($i = 0; $i < 200; $i++) {
        $ip = implode('.', [$publicFirstOctets[array_rand($publicFirstOctets)], mt_rand(0, 255), mt_rand(0, 255), mt_rand(1, 254)]);

        $result = SafeWebhookUrl::validate("https://{$ip}/hook");

        expect($result['valid'])->toBeTrue("Rejected public {$ip} (FUZZ_SEED={$this->seed})")
            ->and(SafeWebhookUrl::httpOptions("https://{$ip}/hook")['curl'][CURLOPT_RESOLVE])->toBe(["{$ip}:443:{$ip}"]);
    }
});
