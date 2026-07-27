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
