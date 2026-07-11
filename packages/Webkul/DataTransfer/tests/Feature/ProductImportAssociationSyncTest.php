<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Models\Product;

/**
 * Closes the last unsynced association write path: the product bulk
 * importer (`saveProducts()` -> `bulkInsertProducts()`/`bulkUpdateProducts()`)
 * writes the legacy `values['associations']` JSON via raw `DB::table('products')`
 * writes, bypassing Eloquent entirely — so nothing mirrors it into the
 * `product_associations` link table like admin/API/copy already do.
 *
 * Drives the importer's public `saveProducts()` choke point directly (the
 * brief's fallback for a heavy job-track/batch harness), with a `$products`
 * payload shaped exactly like `prepareProducts()`/`prepareOtherSections()`
 * produce, to prove the sync now happens after the bulk DB write.
 */
describe('Product import association link-table sync', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('syncs legacy up_sells associations from a bulk product import into the link table', function () {
        $upSellA = Product::factory()->withInitialValues()->create();
        $upSellB = Product::factory()->withInitialValues()->create();

        $sku = 'IMPORT-ASSOC-'.uniqid();

        $familyId = AttributeFamily::where('code', 'default')->value('id');

        $products = [
            'insert' => [
                $sku => [
                    'sku'                 => $sku,
                    'type'                => 'simple',
                    'parent_id'           => null,
                    'attribute_family_id' => $familyId,
                    'status'              => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                    'values'              => [
                        'common' => [
                            'sku' => $sku,
                        ],
                        'associations' => [
                            'up_sells' => [$upSellA->sku, $upSellB->sku],
                        ],
                    ],
                ],
            ],
        ];

        /** @var Importer $importer */
        $importer = app(Importer::class);

        $importer->saveProducts($products);

        $product = Product::where('sku', $sku)->first();

        expect($product)->not->toBeNull();

        // Legacy JSON is written exactly as before (unaffected by this change).
        expect($product->values['associations']['up_sells'] ?? null)
            ->toBe([$upSellA->sku, $upSellB->sku]);

        // The link table now mirrors it — this is the gap being closed.
        $upSellsCount = DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $product->id)
            ->where('association_types.code', 'up_sells')
            ->count();

        expect($upSellsCount)->toBe(2);
    });
});
