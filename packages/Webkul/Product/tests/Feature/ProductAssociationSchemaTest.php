<?php

use Illuminate\Support\Facades\Schema;

it('creates the product_associations table with its columns', function () {
    expect(Schema::hasTable('product_associations'))->toBeTrue()
        ->and(Schema::hasColumns('product_associations', [
            'id', 'product_id', 'association_type_id', 'related_product_id', 'position', 'additional_data',
        ]))->toBeTrue();
});
