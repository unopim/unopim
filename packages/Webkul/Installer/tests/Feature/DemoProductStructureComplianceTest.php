<?php

use Webkul\Installer\Database\Seeders\Demo\DemoAttributeSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCategorySeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCoreSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoFamilySeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

/**
 * The seeded catalog has to obey the rule the product form enforces: a row may
 * only hold values for attributes the variant structure places at its own
 * level, sku excepted.
 *
 * @see ProductRepository
 */
function seedDemoCatalog(): void
{
    resolve(DemoCoreSeeder::class)->run();
    resolve(DemoAttributeSeeder::class)->run();
    resolve(DemoFamilySeeder::class)->run();
    resolve(DemoCategorySeeder::class)->run();
    resolve(DemoProductSeeder::class)->run();
}

/** Every value code a row carries, across all scopes. */
function carriedCodes(Product $product): array
{
    $values = $product->values ?? [];

    $codes = array_keys($values['common'] ?? []);

    foreach ($values['locale_specific'] ?? [] as $byLocale) {
        $codes = [...$codes, ...array_keys($byLocale)];
    }

    foreach ($values['channel_specific'] ?? [] as $byChannel) {
        $codes = [...$codes, ...array_keys($byChannel)];
    }

    foreach ($values['channel_locale_specific'] ?? [] as $byChannel) {
        foreach ($byChannel as $byLocale) {
            $codes = [...$codes, ...array_keys($byLocale)];
        }
    }

    return array_values(array_unique($codes));
}

it('never writes a value onto a row the variant structure does not let own it', function () {
    seedDemoCatalog();

    $planner = resolve(VariantStructurePlanner::class);

    $offenders = [];

    Product::query()->whereNotNull('variant_structure_id')->cursor()->each(
        function (Product $configurable) use ($planner, &$offenders): void {
            $rows = [$configurable, ...$configurable->variants()->with('variants')->get()->flatMap(
                fn (Product $child): array => [$child, ...$child->variants]
            )];

            foreach ($rows as $row) {
                foreach (carriedCodes($row) as $code) {
                    if ($code === 'sku' || $planner->ownsAtOwnLevel($row, $code)) {
                        continue;
                    }

                    $offenders[] = "{$row->type} {$row->sku} carries $code";
                }
            }
        }
    );

    expect($offenders)->toBe([]);
});

it('gives every configurable a name and every simple variant its own price', function () {
    seedDemoCatalog();

    $configurable = Product::query()->where('type', 'configurable')->firstOrFail();

    expect($configurable->values['channel_locale_specific']['default']['en_US']['name'] ?? null)->not->toBeNull();

    $simple = $configurable->variants()->where('type', 'simple')->first()
        ?? $configurable->variants()->firstOrFail()->variants()->firstOrFail();

    expect($simple->values['channel_locale_specific']['default']['en_US']['price'] ?? null)->not->toBeNull()
        ->and($simple->resolvedValues()['channel_locale_specific']['default']['en_US']['name'] ?? null)->not->toBeNull();
});

it('gives every row below a configurable a unique url_key and product number', function () {
    seedDemoCatalog();

    $simples = Product::query()->where('type', 'simple')->whereNotNull('parent_id')->get();

    $urlKeys = $simples->pluck('values.common.url_key')->filter()->all();
    $numbers = $simples->pluck('values.common.product_number')->filter()->all();

    expect($urlKeys)->toHaveCount($simples->count())
        ->and(array_unique($urlKeys))->toHaveCount(count($urlKeys))
        ->and($numbers)->toHaveCount($simples->count())
        ->and(array_unique($numbers))->toHaveCount(count($numbers));
});
