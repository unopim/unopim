<?php

use Webkul\AppUrlGuard\Concerns\NormalizesUrl;

/**
 * Exhaustive matrix for the URL normaliser — the single source of truth that
 * decides whether two base URLs are "the same". A bug here is a false warning
 * (or a missed one), so every case is pinned down explicitly.
 */
function normalizer(): object
{
    return new class
    {
        use NormalizesUrl;

        public function run(string $url): string
        {
            return $this->normalize($url);
        }

        public function same(string $a, string $b): bool
        {
            return $this->matches($a, $b);
        }
    };
}

dataset('equal urls', [
    'identical'                  => ['http://site.test', 'http://site.test'],
    'trailing slash'             => ['http://site.test/', 'http://site.test'],
    'upper-case host'            => ['HTTP://Site.TEST', 'http://site.test'],
    'surrounding whitespace'     => ['   http://site.test   ', 'http://site.test'],
    'http default port'          => ['http://site.test:80', 'http://site.test'],
    'https default port'         => ['https://site.test:443', 'https://site.test'],
    'default port + sub-path'    => ['http://site.test:80/erp', 'http://site.test/erp'],
    'sub-path trailing slash'    => ['http://site.test/erp/', 'http://site.test/erp'],
    'mixed case + slash + port'  => ['HTTP://Site.TEST:80/', 'http://site.test'],
]);

dataset('different urls', [
    'different host'             => ['http://a.test', 'http://b.test'],
    'different scheme'           => ['http://site.test', 'https://site.test'],
    'non-default port'           => ['http://site.test:8000', 'http://site.test:8090'],
    'explicit vs no port'        => ['http://site.test:8080', 'http://site.test'],
    'sub-path present vs absent' => ['http://site.test/erp', 'http://site.test'],
    'different sub-path'         => ['http://site.test/erp', 'http://site.test/crm'],
    'http :443 is not default'   => ['http://site.test:443', 'http://site.test'],
    'case-sensitive sub-path'    => ['http://site.test/ERP', 'http://site.test/erp'],
]);

it('treats equal urls as equal', function (string $a, string $b) {
    expect(normalizer()->run($a))->toBe(normalizer()->run($b));
})->with('equal urls');

it('treats different urls as different', function (string $a, string $b) {
    expect(normalizer()->run($a))->not->toBe(normalizer()->run($b));
})->with('different urls');

it('returns an empty string for empty / whitespace input', function (string $input) {
    expect(normalizer()->run($input))->toBe('');
})->with([
    'empty'      => [''],
    'spaces'     => ['   '],
    'slash only' => ['/'],
]);

it('treats loopback hosts as the same origin', function (string $a, string $b) {
    expect(normalizer()->same($a, $b))->toBeTrue();
})->with([
    'localhost vs 127.0.0.1'     => ['http://localhost:8000', 'http://127.0.0.1:8000'],
    'localhost vs ipv6 loopback' => ['http://localhost', 'http://[::1]'],
    '127.0.0.1 vs ipv6 loopback' => ['http://127.0.0.1:9000', 'http://[::1]:9000'],
    'loopback with sub-path'     => ['http://localhost/erp', 'http://127.0.0.1/erp'],
    'loopback default port'      => ['http://localhost', 'http://127.0.0.1:80'],
]);

it('keeps loopback hosts distinct when scheme, port or path differ', function (string $a, string $b) {
    expect(normalizer()->same($a, $b))->toBeFalse();
})->with([
    'different port'   => ['http://localhost:8000', 'http://127.0.0.1:8090'],
    'different scheme' => ['http://localhost:8000', 'https://localhost:8000'],
    'different path'   => ['http://localhost/erp', 'http://127.0.0.1/crm'],
    'loopback vs real' => ['http://localhost', 'http://site.test'],
]);
