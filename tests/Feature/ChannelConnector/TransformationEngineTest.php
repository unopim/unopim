<?php

use Webkul\ChannelConnector\Services\TransformationEngine;

it('applies uppercase transformation', function () {
    $result = TransformationEngine::apply('hello world', [
        ['type' => 'uppercase'],
    ]);

    expect($result)->toBe('HELLO WORLD');
});

it('applies lowercase transformation', function () {
    $result = TransformationEngine::apply('HELLO WORLD', [
        ['type' => 'lowercase'],
    ]);

    expect($result)->toBe('hello world');
});

it('applies capitalize transformation', function () {
    $result = TransformationEngine::apply('hello world', [
        ['type' => 'capitalize'],
    ]);

    expect($result)->toBe('Hello World');
});

it('applies prefix transformation', function () {
    $result = TransformationEngine::apply('12345', [
        ['type' => 'prefix', 'config' => ['value' => 'SKU-']],
    ]);

    expect($result)->toBe('SKU-12345');
});

it('applies suffix transformation', function () {
    $result = TransformationEngine::apply('Test', [
        ['type' => 'suffix', 'config' => ['value' => ' (NEW)']],
    ]);

    expect($result)->toBe('Test (NEW)');
});

it('applies replace transformation', function () {
    $result = TransformationEngine::apply('Hello World', [
        ['type' => 'replace', 'config' => ['search' => 'World', 'replacement' => 'UnoPim']],
    ]);

    expect($result)->toBe('Hello UnoPim');
});

it('applies number_format transformation', function () {
    $result = TransformationEngine::apply(1234.5, [
        ['type' => 'number_format', 'config' => ['decimals' => 2]],
    ]);

    expect($result)->toBe('1234.50');
});

it('applies currency_convert transformation', function () {
    $result = TransformationEngine::apply(100, [
        ['type' => 'currency_convert', 'config' => ['rate' => 3.75]],
    ]);

    expect($result)->toBe(375.0);
});

it('applies map_values transformation', function () {
    $result = TransformationEngine::apply('active', [
        ['type' => 'map_values', 'config' => ['mapping' => ['active' => 'ACTIVE', 'draft' => 'DRAFT']]],
    ]);

    expect($result)->toBe('ACTIVE');
});

it('returns original value when map_values has no match', function () {
    $result = TransformationEngine::apply('unknown', [
        ['type' => 'map_values', 'config' => ['mapping' => ['active' => 'ACTIVE']]],
    ]);

    expect($result)->toBe('unknown');
});

it('applies truncate transformation', function () {
    $longString = str_repeat('a', 300);
    $result = TransformationEngine::apply($longString, [
        ['type' => 'truncate', 'config' => ['max_length' => 100, 'ellipsis' => '...']],
    ]);

    expect(mb_strlen($result))->toBe(100);
    expect($result)->toEndWith('...');
});

it('does not truncate short strings', function () {
    $result = TransformationEngine::apply('short', [
        ['type' => 'truncate', 'config' => ['max_length' => 100]],
    ]);

    expect($result)->toBe('short');
});

it('applies strip_html transformation', function () {
    $result = TransformationEngine::apply('<p>Hello <b>World</b></p>', [
        ['type' => 'strip_html'],
    ]);

    expect($result)->toBe('Hello World');
});

it('applies default_value transformation when value is null', function () {
    $result = TransformationEngine::apply(null, [
        ['type' => 'default_value', 'config' => ['value' => 'N/A']],
    ]);

    expect($result)->toBe('N/A');
});

it('applies default_value transformation when value is empty string', function () {
    $result = TransformationEngine::apply('', [
        ['type' => 'default_value', 'config' => ['value' => 'N/A']],
    ]);

    expect($result)->toBe('N/A');
});

it('does not apply default_value when value exists', function () {
    $result = TransformationEngine::apply('Has Value', [
        ['type' => 'default_value', 'config' => ['value' => 'N/A']],
    ]);

    expect($result)->toBe('Has Value');
});

it('applies pipeline of multiple transformations in order', function () {
    $result = TransformationEngine::apply('  hello world  ', [
        ['type' => 'uppercase'],
        ['type' => 'prefix', 'config' => ['value' => '[']],
        ['type' => 'suffix', 'config' => ['value' => ']']],
    ]);

    expect($result)->toBe('[  HELLO WORLD  ]');
});

it('handles real-world SKU transformation pipeline', function () {
    $result = TransformationEngine::apply('widget-123', [
        ['type' => 'uppercase'],
        ['type' => 'replace', 'config' => ['search' => '-', 'replacement' => '_']],
        ['type' => 'prefix', 'config' => ['value' => 'SHOP-']],
    ]);

    expect($result)->toBe('SHOP-WIDGET_123');
});

it('handles price conversion pipeline', function () {
    $result = TransformationEngine::apply(29.99, [
        ['type' => 'currency_convert', 'config' => ['rate' => 3.75]],
        ['type' => 'number_format', 'config' => ['decimals' => 2]],
    ]);

    expect($result)->toBe('112.46');
});

it('skips unknown transformation types', function () {
    $result = TransformationEngine::apply('test', [
        ['type' => 'unknown_transform'],
    ]);

    expect($result)->toBe('test');
});

it('skips rules without type', function () {
    $result = TransformationEngine::apply('test', [
        ['config' => ['value' => 'something']],
    ]);

    expect($result)->toBe('test');
});

it('handles non-string values for string transforms gracefully', function () {
    $result = TransformationEngine::apply(12345, [
        ['type' => 'uppercase'],
    ]);

    expect($result)->toBe(12345);
});

it('handles empty rules array', function () {
    $result = TransformationEngine::apply('unchanged', []);

    expect($result)->toBe('unchanged');
});
