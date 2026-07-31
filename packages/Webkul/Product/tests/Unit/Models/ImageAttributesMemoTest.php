<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

/*
 * Guards against M1: getImageAttributes() ran a four-table join per product.
 * In normalizeWithImage() loops (search results, configurable variants) that is
 * one join per row. Image attributes are a family-level constant, so resolving
 * them for a second product of the same family must not hit the database again.
 */
it('memoizes image attributes per family across product instances (M1)', function () {
    $family = AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

    $first = Product::factory()->simple()->create(['attribute_family_id' => $family->id]);
    $second = Product::factory()->simple()->create(['attribute_family_id' => $family->id]);

    $first->getImageAttributes();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $second->getImageAttributes();

    expect(DB::getQueryLog())->toHaveCount(0);
});
