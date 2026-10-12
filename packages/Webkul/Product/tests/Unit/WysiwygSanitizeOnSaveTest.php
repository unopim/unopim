<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

function invokeProcessValues(Product $product, array $values): array
{
    $type = $product->getTypeInstance();

    $method = new ReflectionMethod($type, 'processValues');
    $method->setAccessible(true);

    return $method->invoke($type, $product->id, $values);
}

it('strips script and event handlers from wysiwyg values on save', function () {
    $attribute = Attribute::factory()->create([
        'code'           => 'rich_desc',
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
    ]);

    $product = Product::factory()->simple()->create([
        'values' => ['common' => ['sku' => 'XSS-1']],
    ]);

    $malicious = '<p>Hello</p><script>alert(1)</script><img src=x onerror=alert(2)><a href="javascript:alert(3)">x</a>';

    $result = invokeProcessValues($product, ['rich_desc' => $malicious]);

    expect($result['rich_desc'])
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->toContain('Hello');
});

it('leaves non-wysiwyg textarea values untouched', function () {
    Attribute::factory()->create([
        'code'           => 'plain_note',
        'type'           => 'textarea',
        'enable_wysiwyg' => 0,
    ]);

    $product = Product::factory()->simple()->create([
        'values' => ['common' => ['sku' => 'XSS-2']],
    ]);

    $raw = 'line one < line two & three';

    $result = invokeProcessValues($product, ['plain_note' => $raw]);

    expect($result['plain_note'])->toBe($raw);
});
