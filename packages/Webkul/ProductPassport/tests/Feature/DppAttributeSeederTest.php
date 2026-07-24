<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\ProductPassport\Database\Seeders\DppAttributeSeeder;

it('seeds the dpp group idempotently', function (): void {
    resolve(DppAttributeSeeder::class)->run();
    resolve(DppAttributeSeeder::class)->run();

    expect(AttributeGroup::where('code', 'dpp')->count())->toBe(1)
        ->and(Attribute::where('code', 'dpp_material_composition')->count())->toBe(1);
});

it('marks human-readable attributes as per-locale', function (): void {
    resolve(DppAttributeSeeder::class)->run();

    expect(Attribute::where('code', 'dpp_care_instructions')->value('value_per_locale'))->toBe(1)
        ->and(Attribute::where('code', 'dpp_gtin')->value('value_per_locale'))->toBe(0);
});

it('restricts every file attribute to pdf, excluding svg', function (): void {
    resolve(DppAttributeSeeder::class)->run();

    expect(Attribute::where('code', 'dpp_certificates')->first()->resolvedAllowedExtensions())->toBe(['pdf']);
});
