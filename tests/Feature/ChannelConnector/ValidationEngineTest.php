<?php

use Webkul\ChannelConnector\Services\ValidationEngine;

it('validates required field', function () {
    $payload = ['common' => ['title' => null], 'locales' => []];
    $rules = ['title' => [['type' => 'required']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]['rule'])->toBe('required');
    expect($result->errors[0]['field'])->toBe('title');
});

it('validates required field passes', function () {
    $payload = ['common' => ['title' => 'Product'], 'locales' => []];
    $rules = ['title' => [['type' => 'required']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
});

it('validates min_length', function () {
    $payload = ['common' => ['title' => 'AB'], 'locales' => []];
    $rules = ['title' => [['type' => 'min_length', 'config' => ['length' => 3]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('min_length');
});

it('validates max_length', function () {
    $payload = ['common' => ['title' => str_repeat('A', 300)], 'locales' => []];
    $rules = ['title' => [['type' => 'max_length', 'config' => ['length' => 255]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('max_length');
});

it('validates regex', function () {
    $payload = ['common' => ['sku' => 'abc'], 'locales' => []];
    $rules = ['sku' => [['type' => 'regex', 'config' => ['pattern' => '/^[A-Z]/']]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('regex');
});

it('validates numeric_range', function () {
    $payload = ['common' => ['price' => -1], 'locales' => []];
    $rules = ['price' => [['type' => 'numeric_range', 'config' => ['min' => 0]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('numeric_range');
});

it('validates in_list', function () {
    $payload = ['common' => ['status' => 'unknown'], 'locales' => []];
    $rules = ['status' => [['type' => 'in_list', 'config' => ['values' => ['active', 'draft']]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('in_list');
});

it('validates url', function () {
    $payload = ['common' => ['website' => 'not-a-url'], 'locales' => []];
    $rules = ['website' => [['type' => 'url']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('url');
});

it('passes with empty rules', function () {
    $payload = ['common' => ['title' => 'Product'], 'locales' => []];
    $rules = [];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
});

it('validates multiple rules per field', function () {
    $payload = ['common' => ['title' => ''], 'locales' => []];
    $rules = [
        'title' => [
            ['type' => 'required'],
            ['type' => 'min_length', 'config' => ['length' => 3]],
        ],
    ];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors)->not->toBeEmpty();

    $ruleTypes = array_column($result->errors, 'rule');
    expect($ruleTypes)->toContain('required');
});

it('validates locale-specific fields', function () {
    $payload = [
        'common'  => ['title' => 'Valid Title'],
        'locales' => [
            'en_US' => ['title' => 'AB'],
            'ar_AE' => ['title' => 'Valid Arabic Title'],
        ],
    ];
    $rules = ['title' => [['type' => 'min_length', 'config' => ['length' => 3]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();

    $failedFields = array_column($result->errors, 'field');
    expect($failedFields)->toContain('title (en_US)');
});

it('validates across multiple locales', function () {
    $payload = [
        'common'  => [],
        'locales' => [
            'en_US' => ['title' => null],
            'ar_AE' => ['title' => null],
        ],
    ];
    $rules = ['title' => [['type' => 'required']]];

    $result = ValidationEngine::validate($payload, $rules);

    // common (null) + en_US (null) + ar_AE (null) = 3 errors
    expect($result->valid)->toBeFalse();
    expect($result->errors)->toHaveCount(3);
});

it('ignores unknown rule types gracefully', function () {
    $payload = ['common' => ['title' => 'Product'], 'locales' => []];
    $rules = ['title' => [['type' => 'nonexistent_rule']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
});

it('skips min_length validation for null values', function () {
    $payload = ['common' => ['title' => null], 'locales' => []];
    $rules = ['title' => [['type' => 'min_length', 'config' => ['length' => 3]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
});

it('skips max_length validation for empty string', function () {
    $payload = ['common' => ['title' => ''], 'locales' => []];
    $rules = ['title' => [['type' => 'max_length', 'config' => ['length' => 10]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
});

it('validates numeric_range max boundary', function () {
    $payload = ['common' => ['price' => 1000], 'locales' => []];
    $rules = ['price' => [['type' => 'numeric_range', 'config' => ['max' => 999.99]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('numeric_range');
});

it('validates numeric_range rejects non-numeric values', function () {
    $payload = ['common' => ['price' => 'not-a-number'], 'locales' => []];
    $rules = ['price' => [['type' => 'numeric_range', 'config' => ['min' => 0]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['message'])->toContain('numeric');
});

it('validates url accepts valid URLs', function () {
    $payload = ['common' => ['website' => 'https://example.com/path?q=1'], 'locales' => []];
    $rules = ['website' => [['type' => 'url']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
});

it('validates required treats empty string as missing', function () {
    $payload = ['common' => ['title' => ''], 'locales' => []];
    $rules = ['title' => [['type' => 'required']]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('required');
});

it('validates in_list accepts valid value', function () {
    $payload = ['common' => ['status' => 'active'], 'locales' => []];
    $rules = ['status' => [['type' => 'in_list', 'config' => ['values' => ['active', 'draft']]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
});

it('validates regex passes for matching pattern', function () {
    $payload = ['common' => ['sku' => 'SKU-001-ABC'], 'locales' => []];
    $rules = ['sku' => [['type' => 'regex', 'config' => ['pattern' => '/^SKU-\\d{3}-.+$/']]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeTrue();
});

it('validates min_length with multibyte characters', function () {
    $payload = ['common' => ['title' => 'مر'], 'locales' => []];
    $rules = ['title' => [['type' => 'min_length', 'config' => ['length' => 3]]]];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors[0]['rule'])->toBe('min_length');
});

it('validates multiple fields independently', function () {
    $payload = ['common' => ['title' => '', 'price' => -5], 'locales' => []];
    $rules = [
        'title' => [['type' => 'required']],
        'price' => [['type' => 'numeric_range', 'config' => ['min' => 0]]],
    ];

    $result = ValidationEngine::validate($payload, $rules);

    expect($result->valid)->toBeFalse();
    expect($result->errors)->toHaveCount(2);

    $failedFields = array_column($result->errors, 'field');
    expect($failedFields)->toContain('title');
    expect($failedFields)->toContain('price');
});
