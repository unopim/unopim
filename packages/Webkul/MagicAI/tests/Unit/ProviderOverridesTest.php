<?php

use Webkul\MagicAI\Services\ProviderOverrides;

it('decodes a JSON string into an array', function () {
    expect(ProviderOverrides::decode('{"organization":"org-123"}'))
        ->toBe(['organization' => 'org-123']);
});

it('returns an empty array for unusable extras', function (mixed $extras) {
    expect(ProviderOverrides::decode($extras))->toBe([]);
})->with([
    [null],
    [''],
    ['not-json'],
    [42],
]);

it('strips reserved endpoint and credential keys from extras', function (string $key) {
    expect(ProviderOverrides::decode([$key => 'http://169.254.169.254', 'keep' => 'me']))
        ->toBe(['keep' => 'me']);
})->with(ProviderOverrides::RESERVED_KEYS);

it('strips reserved keys case-insensitively so a legacy uppercase entry cannot slip through', function () {
    expect(ProviderOverrides::decode(['URL' => 'http://internal', 'Api_Key' => 'leak', 'deployment' => 'gpt-4o']))
        ->toBe(['deployment' => 'gpt-4o']);
});

it('merges extras over the base without letting them redefine the platform url or key', function () {
    $overrides = ProviderOverrides::build(
        ['key' => 'sk-real', 'url' => 'https://api.openai.com/v1'],
        '{"url":"http://127.0.0.1:9200","key":"sk-attacker","organization":"org-1"}',
    );

    expect($overrides)->toBe([
        'key'          => 'sk-real',
        'url'          => 'https://api.openai.com/v1',
        'organization' => 'org-1',
    ]);
});

it('returns the base untouched when extras decode to nothing', function () {
    expect(ProviderOverrides::build(['key' => 'sk-real'], null))->toBe(['key' => 'sk-real']);
});
