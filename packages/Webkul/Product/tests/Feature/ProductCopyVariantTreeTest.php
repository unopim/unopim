<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Copying a 2-level configurable (configurable → variant_group → simple) used to duplicate
 * only the variant_group nodes and drop the simple products beneath them.
 */
it('copies the simple products under each variant group of a 2-level configurable', function () {
    $configurable = Product::factory()->withInitialValues()->create(['type' => ProductType::Configurable->value]);

    $groups = collect(range(1, 2))->map(fn () => Product::factory()->create([
        'type'      => ProductType::VariantGroup->value,
        'parent_id' => $configurable->id,
    ]));

    $groups->each(fn ($group) => Product::factory()->create([
        'type'      => ProductType::Simple->value,
        'parent_id' => $group->id,
    ]));

    $copy = app(ProductRepository::class)->copy($configurable->id);

    $copiedGroups = Product::where('parent_id', $copy->id)->get();

    expect($copiedGroups)->toHaveCount(2);

    foreach ($copiedGroups as $copiedGroup) {
        expect($copiedGroup->type)->toBe(ProductType::VariantGroup->value);

        $leaves = Product::where('parent_id', $copiedGroup->id)->get();

        expect($leaves)->toHaveCount(1)
            ->and($leaves->first()->type)->toBe(ProductType::Simple->value);
    }

    // The source tree is untouched.
    expect(Product::where('parent_id', $groups->first()->id)->count())->toBe(1);
});
