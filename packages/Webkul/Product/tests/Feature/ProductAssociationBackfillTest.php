<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

/**
 * Loads and runs the backfill migration's up() method directly, bypassing
 * `php artisan migrate` so the test stays isolated inside its own DB
 * transaction and does not touch/duplicate pre-existing production rows.
 */
function runProductAssociationBackfill(): void
{
    $migration = require base_path(
        'packages/Webkul/Product/src/Database/Migrations/2026_07_11_100002_backfill_product_associations.php'
    );

    $migration->up();
}

it('backfills product_associations from legacy JSON, skipping ghost SKUs and self-links', function () {
    $upSell = Product::factory()->create();

    $source = Product::factory()->create();

    // Bypass the repository/dual-write path deliberately: we only want the
    // legacy JSON populated, so the backfill migration is what creates the
    // link-table rows, not the (already tested) dual-write on save.
    $source->values = [
        'common'       => ['sku' => $source->sku],
        'associations' => [
            'up_sells'         => [$upSell->sku, 'GHOST'],
            'related_products' => [$source->sku],
        ],
    ];
    $source->save();

    runProductAssociationBackfill();

    $upSellsCount = DB::table('product_associations')
        ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
        ->where('product_associations.product_id', $source->id)
        ->where('association_types.code', 'up_sells')
        ->count();

    $relatedCount = DB::table('product_associations')
        ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
        ->where('product_associations.product_id', $source->id)
        ->where('association_types.code', 'related_products')
        ->count();

    expect($upSellsCount)->toBe(1)
        ->and($relatedCount)->toBe(0);

    $link = DB::table('product_associations')
        ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
        ->where('product_associations.product_id', $source->id)
        ->where('association_types.code', 'up_sells')
        ->first(['product_associations.related_product_id', 'product_associations.additional_data']);

    expect($link->related_product_id)->toBe($upSell->id)
        ->and($link->additional_data)->toBeNull();

    $totalBefore = DB::table('product_associations')->where('product_id', $source->id)->count();

    // Idempotency: running the backfill again must not create duplicates.
    runProductAssociationBackfill();

    $totalAfter = DB::table('product_associations')->where('product_id', $source->id)->count();

    expect($totalAfter)->toBe($totalBefore);
});

it('does not overwrite additional_data on an existing product_associations row when re-run', function () {
    $upSell = Product::factory()->create();

    $source = Product::factory()->create();

    $source->values = [
        'common'       => ['sku' => $source->sku],
        'associations' => [
            'up_sells' => [$upSell->sku],
        ],
    ];
    $source->save();

    $associationTypeId = DB::table('association_types')->where('code', 'up_sells')->value('id');

    // Pre-create the corresponding link row with real, user-set
    // additional_data (e.g. a Task 3 per-link quantity), simulating a row
    // that already exists alongside the legacy JSON counterpart.
    DB::table('product_associations')->insert([
        'product_id'          => $source->id,
        'association_type_id' => $associationTypeId,
        'related_product_id'  => $upSell->id,
        'position'            => 3,
        'additional_data'     => json_encode(['common' => ['quantity' => '5']]),
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    runProductAssociationBackfill();

    $rows = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $associationTypeId)
        ->where('related_product_id', $upSell->id)
        ->get();

    expect($rows)->toHaveCount(1);

    $row = $rows->first();

    expect($row->position)->toBe(3)
        ->and(json_decode($row->additional_data, true))->toBe(['common' => ['quantity' => '5']]);
});
