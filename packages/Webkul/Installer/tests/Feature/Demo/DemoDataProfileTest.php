<?php

use Webkul\Installer\Demo\DemoDataProfile;

it('builds the minimal preset with zero families', function () {
    $profile = DemoDataProfile::fromPreset(DemoDataProfile::PRESET_MINIMAL, ['en_US']);

    expect($profile->preset)->toBe(DemoDataProfile::PRESET_MINIMAL);
    expect($profile->families)->toBe([]);
    expect($profile->isMinimal())->toBeTrue();
});

it('builds the starter preset with only food_grocery', function () {
    $profile = DemoDataProfile::fromPreset(DemoDataProfile::PRESET_STARTER, ['en_US']);

    expect($profile->preset)->toBe(DemoDataProfile::PRESET_STARTER);
    expect($profile->families)->toBe(['food_grocery']);
    expect($profile->isMinimal())->toBeFalse();
    expect($profile->shouldSeedFamily('food_grocery'))->toBeTrue();
    expect($profile->shouldSeedFamily('fashion_apparel'))->toBeFalse();
});

it('builds the medium preset with 8 families across 4 industries', function () {
    $profile = DemoDataProfile::fromPreset(DemoDataProfile::PRESET_MEDIUM, ['en_US']);

    expect($profile->preset)->toBe(DemoDataProfile::PRESET_MEDIUM);
    expect(count($profile->families))->toBe(8);
    expect($profile->families)->toContain('food_grocery');
    expect($profile->families)->toContain('fashion_apparel');
    expect($profile->families)->toContain('pharma_otc');
    expect($profile->families)->toContain('manufacturing_industrial');
});

it('builds the full preset with all 20 families flattened from the industry map', function () {
    $profile = DemoDataProfile::fromPreset(DemoDataProfile::PRESET_FULL, ['en_US']);

    expect($profile->preset)->toBe(DemoDataProfile::PRESET_FULL);

    $expectedCount = array_sum(array_map(
        'count',
        DemoDataProfile::INDUSTRIES_TO_FAMILIES
    ));
    expect(count($profile->families))->toBe($expectedCount);

    // Every industry page on unopim.com has at least one family
    foreach (array_keys(DemoDataProfile::INDUSTRIES_TO_FAMILIES) as $industry) {
        $industryFamilies = DemoDataProfile::INDUSTRIES_TO_FAMILIES[$industry];
        foreach ($industryFamilies as $family) {
            expect($profile->shouldSeedFamily($family))->toBeTrue(
                "{$industry} industry's family {$family} missing from full preset"
            );
        }
    }
});

it('always includes the user default locale in every preset', function () {
    $profile = DemoDataProfile::fromPreset(DemoDataProfile::PRESET_STARTER, ['nl_NL']);

    expect($profile->shouldSeedLocale('nl_NL'))->toBeTrue();
    expect($profile->shouldSeedLocale('en_US'))->toBeTrue();
});

it('rejects unknown preset names with a clear exception', function () {
    DemoDataProfile::fromPreset('bogus');
})->throws(InvalidArgumentException::class, 'Unknown demo preset: bogus');

it('custom() builds a fully user-specified profile', function () {
    $profile = DemoDataProfile::custom(
        families: ['food_grocery', 'fashion_footwear'],
        locales: ['en_US', 'de_DE', 'ja_JP'],
        channels: ['ecommerce', 'print_catalogue'],
    );

    expect($profile->preset)->toBe(DemoDataProfile::PRESET_CUSTOM);
    expect($profile->families)->toBe(['food_grocery', 'fashion_footwear']);
    expect($profile->shouldSeedLocale('ja_JP'))->toBeTrue();
    expect($profile->shouldSeedChannel('print_catalogue'))->toBeTrue();
    expect($profile->shouldSeedChannel('mobile_app'))->toBeFalse();
});

it('covers every unopim.com industry in INDUSTRIES_TO_FAMILIES', function () {
    $siteIndustries = ['food', 'cpg', 'fashion', 'pharmacy', 'manufacturing', 'engineering', 'energy', 'retail'];

    foreach ($siteIndustries as $industry) {
        expect(array_key_exists($industry, DemoDataProfile::INDUSTRIES_TO_FAMILIES))
            ->toBeTrue("unopim.com industry '{$industry}' missing from INDUSTRIES_TO_FAMILIES");
    }
});
