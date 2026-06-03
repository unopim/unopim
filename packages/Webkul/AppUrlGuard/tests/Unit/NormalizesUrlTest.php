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
