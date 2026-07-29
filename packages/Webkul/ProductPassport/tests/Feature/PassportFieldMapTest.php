<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\ProductPassport\Services\PassportPayloadBuilder;

it('sources a dpp field from the mapped attribute when the dpp attribute is empty', function (): void {
    CoreConfig::query()->create([
        'code'         => 'catalog.product_passport.mapping.dpp_country_of_origin',
        'value'        => 'country',
        'channel_code' => null,
        'locale_code'  => null,
    ]);

    [$product, $context] = $this->makeProductWithValues([
        'country'               => 'Germany',
        'dpp_country_of_origin' => null,
    ]);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $codes = array_column($payload['sections'][0]['fields'], 'value', 'code');

    expect($codes['dpp_country_of_origin'] ?? null)->toBe('Germany');
});

it('falls back to the dpp attribute when no mapping exists', function (): void {
    [$product, $context] = $this->makeProductWithValues(['dpp_country_of_origin' => 'France']);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $codes = array_column($payload['sections'][0]['fields'], 'value', 'code');

    expect($codes['dpp_country_of_origin'] ?? null)->toBe('France');
});

it('surfaces a configured custom field with its typed label and the mapped value', function (): void {
    CoreConfig::query()->create([
        'code'         => 'catalog.product_passport.custom_fields',
        'value'        => json_encode([['name' => 'Origin Country', 'attribute' => 'country']]),
        'channel_code' => null,
        'locale_code'  => null,
    ]);

    [$product, $context] = $this->makeProductWithValues(['country' => 'Germany']);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('label', 'Origin Country');

    expect($field)->not->toBeNull()
        ->and($field['value'])->toBe('Germany')
        ->and($payload['tiers']['consumer']['fields'])->toContain($field);
});

it('skips a custom field whose mapped attribute has no value', function (): void {
    CoreConfig::query()->create([
        'code'         => 'catalog.product_passport.custom_fields',
        'value'        => json_encode([['name' => 'Origin Country', 'attribute' => 'country']]),
        'channel_code' => null,
        'locale_code'  => null,
    ]);

    [$product, $context] = $this->makeProductWithValues(['country' => null]);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    expect(collect($payload['sections'][0]['fields'])->firstWhere('label', 'Origin Country'))->toBeNull();
});

it('adds no custom rows when no custom fields are configured', function (): void {
    [$product, $context] = $this->makeProductWithValues(['dpp_country_of_origin' => 'France']);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $codes = array_column($payload['sections'][0]['fields'], 'code');

    expect($codes)->toBe(['dpp_country_of_origin'])
        ->and($codes)->each->not->toStartWith('custom_');
});
