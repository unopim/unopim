<?php

use Webkul\MagicAI\Rules\SafeProviderExtras;

function validateExtras(mixed $value): array
{
    $failures = [];

    (new SafeProviderExtras)->validate('extras', $value, function (string $message) use (&$failures): void {
        $failures[] = $message;
    });

    return $failures;
}

it('accepts an empty payload', function (mixed $value) {
    expect(validateExtras($value))->toBe([]);
})->with([[null], [''], [[]]]);

it('accepts a well formed JSON object', function () {
    expect(validateExtras('{"organization":"org-123","deployment":"gpt-4o"}'))->toBe([]);
});

it('accepts an array payload by encoding it first', function () {
    expect(validateExtras(['deployment' => 'gpt-4o']))->toBe([]);
});

it('rejects a payload that is not a JSON object', function (string $value) {
    expect(validateExtras($value))->toHaveCount(1);
})->with([
    'not-json',
    '[1,2,3]',
    '"a string"',
    '42',
]);

it('rejects a payload larger than the byte ceiling', function () {
    $payload = json_encode(['blob' => str_repeat('a', 9000)]);

    expect(validateExtras($payload))->toHaveCount(1);
});

it('rejects extras that redefine a reserved endpoint or credential key', function (string $key) {
    $failures = validateExtras(json_encode([$key => 'http://169.254.169.254']));

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain($key);
})->with(['url', 'key', 'api_key', 'base_url']);

it('rejects a reserved key regardless of its casing', function () {
    expect(validateExtras('{"URL":"http://internal"}'))->toHaveCount(1);
});

it('rejects nesting deeper than the allowed depth', function () {
    expect(validateExtras('{"a":{"b":{"c":{"d":{"e":{"f":1}}}}}}'))->toHaveCount(1);
});
