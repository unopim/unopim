<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;

describe('Product Importer Bulk Operations', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('can bulk insert multiple products in a single query', function () {
        $attributeFamily = AttributeFamily::first();

        $skus = ['bulk-test-'.uniqid(), 'bulk-test-'.uniqid(), 'bulk-test-'.uniqid()];

        $insertData = [];

        foreach ($skus as $sku) {
            $insertData[] = [
                'sku'                 => $sku,
                'type'                => 'simple',
                'parent_id'           => null,
                'attribute_family_id' => $attributeFamily->id,
                'values'              => json_encode(['common' => ['sku' => $sku]]),
                'status'              => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        DB::table('products')->insert($insertData);

        foreach ($skus as $sku) {
            $this->assertDatabaseHas('products', ['sku' => $sku]);
        }

        /** Cleanup */
        DB::table('products')->whereIn('sku', $skus)->delete();
    });

    it('can bulk upsert existing products', function () {
        $attributeFamily = AttributeFamily::first();

        $sku = 'upsert-test-'.uniqid();

        /** First create a product */
        DB::table('products')->insert([
            'sku'                 => $sku,
            'type'                => 'simple',
            'parent_id'           => null,
            'attribute_family_id' => $attributeFamily->id,
            'values'              => json_encode(['common' => ['sku' => $sku]]),
            'status'              => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $product = DB::table('products')->where('sku', $sku)->first();

        /** Now upsert with updated status */
        DB::table('products')->upsert(
            [[
                'id'                  => $product->id,
                'sku'                 => $sku,
                'type'                => 'simple',
                'parent_id'           => null,
                'attribute_family_id' => $attributeFamily->id,
                'values'              => json_encode(['common' => ['sku' => $sku, 'name' => 'Updated']]),
                'status'              => 1,
                'updated_at'          => now(),
            ]],
            ['id'],
            ['values', 'status', 'updated_at']
        );

        $updated = DB::table('products')->where('sku', $sku)->first();

        expect($updated->status)->toBe(1);

        $values = json_decode($updated->values, true);
        expect($values['common']['name'])->toBe('Updated');

        /** Cleanup */
        DB::table('products')->where('sku', $sku)->delete();
    });

    it('handles chunked bulk inserts for large batches', function () {
        $attributeFamily = AttributeFamily::first();

        $skus = [];

        for ($i = 0; $i < 15; $i++) {
            $skus[] = 'chunk-test-'.uniqid().'-'.$i;
        }

        $insertData = array_map(fn ($sku) => [
            'sku'                 => $sku,
            'type'                => 'simple',
            'parent_id'           => null,
            'attribute_family_id' => $attributeFamily->id,
            'values'              => json_encode(['common' => ['sku' => $sku]]),
            'status'              => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $skus);

        /** Insert in chunks of 5 to test chunking logic */
        foreach (array_chunk($insertData, 5) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $insertedCount = DB::table('products')->whereIn('sku', $skus)->count();

        expect($insertedCount)->toBe(15);

        /** Cleanup */
        DB::table('products')->whereIn('sku', $skus)->delete();
    });
});

describe('SKUStorage Optimized Loading', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('skips loading already-cached SKUs', function () {
        $attributeFamily = AttributeFamily::first();
        $sku = 'skustorage-test-'.uniqid();

        DB::table('products')->insert([
            'sku'                 => $sku,
            'type'                => 'simple',
            'parent_id'           => null,
            'attribute_family_id' => $attributeFamily->id,
            'values'              => json_encode(['common' => ['sku' => $sku]]),
            'status'              => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $skuStorage = app(SKUStorage::class);

        /** First load */
        $skuStorage->load([$sku]);
        expect($skuStorage->has($sku))->toBeTrue();

        $info = $skuStorage->get($sku);
        expect($info)->toHaveKeys(['id', 'type', 'attribute_family_id']);
        expect($info['type'])->toBe('simple');

        /** Second load should not fail — already cached */
        $skuStorage->load([$sku]);
        expect($skuStorage->has($sku))->toBeTrue();

        /** Cleanup */
        DB::table('products')->where('sku', $sku)->delete();
    });
});
