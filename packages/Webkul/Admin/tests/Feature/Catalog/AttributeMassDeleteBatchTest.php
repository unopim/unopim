<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\postJson;

/*
 * Mass-deleting attributes must resolve the models and their super-attribute
 * usage in bounded queries, not one lookup + one usage count per attribute.
 */
it('checks super-attribute usage in a single query for mass delete', function () {
    $this->loginWithPermissions('all', ['dashboard', 'catalog', 'catalog.attributes', 'catalog.attributes.mass_delete']);

    $ids = collect(range(1, 4))
        ->map(fn (): int => Attribute::factory()->create(['type' => 'text'])->id)
        ->all();

    $superAttributeQueries = 0;

    DB::listen(function ($query) use (&$superAttributeQueries): void {
        if (str_contains($query->sql, 'product_super_attributes')) {
            $superAttributeQueries++;
        }
    });

    postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $ids]);

    expect($superAttributeQueries)->toBeLessThanOrEqual(1);
});
