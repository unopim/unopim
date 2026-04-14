<?php

use Webkul\Installer\Database\Data\Generators\SvgPlaceholderGenerator;

it('renders a valid SVG document for a cola can product', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-COLA-CLASSIC-330ML',
        name: 'GlobalStore Classic Cola 330ml Slim Can',
        brand: 'globalstore_classic',
        packType: 'can',
        netWeightG: '345',
        netVolumeMl: '330',
    );

    expect($svg)->toStartWith('<svg');
    expect($svg)->toContain('viewBox="0 0 200 290"');
    expect($svg)->toContain('GS-COLA-CLASSIC-330ML');
    // The 330ml volume must show up in the badge
    expect($svg)->toContain('330ml');
});

it('falls back to a slate palette for unknown brands', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-UNKNOWN-123',
        name: 'Unknown Product',
        brand: 'non_existent_brand',
        packType: 'box',
        netWeightG: '500',
        netVolumeMl: null,
    );

    // default slate bg
    expect($svg)->toContain('#e2e8f0');
    expect($svg)->toContain('500g');
});

it('formats volumes ≥ 1000ml as litres', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-JUICE-1L',
        name: 'Orange Juice 1L',
        brand: 'orchard_valley',
        packType: 'carton',
        netWeightG: '1040',
        netVolumeMl: '1000',
    );

    expect($svg)->toContain('1.0L');
});

it('formats weights ≥ 1000g as kg', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-RICE-1KG',
        name: 'Arborio Rice 1kg',
        brand: 'harvest_grove',
        packType: 'box',
        netWeightG: '1000',
        netVolumeMl: null,
    );

    expect($svg)->toContain('1.0kg');
});

it('escapes HTML-special characters in SKU and name', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-<script>',
        name: 'Evil <img> Product',
        brand: null,
        packType: null,
        netWeightG: null,
        netVolumeMl: null,
    );

    expect($svg)->not->toContain('<script>');
    expect($svg)->not->toContain('<img>');
    expect($svg)->toContain('&lt;script&gt;');
});

it('wraps long product names into at most 3 lines', function () {
    $generator = new SvgPlaceholderGenerator;

    $svg = $generator->buildSvg(
        sku: 'GS-LONG',
        name: 'This Is A Very Long Product Name That Should Be Wrapped Into Multiple Lines Or Truncated',
        brand: null,
        packType: null,
        netWeightG: null,
        netVolumeMl: null,
    );

    $textMatches = preg_match_all('/<text[^>]*font-size="11"/', $svg);
    expect($textMatches)->toBeLessThanOrEqual(3);
});
