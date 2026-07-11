<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\ProductAssociation\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * Row-per-link association IMPORT job (Plan 4, Task 3): a dedicated import
 * where each row is `sku,association_type,related_sku` plus one column per
 * custom association field (e.g. `quantity`). This is distinct from the
 * wide product CSV's `associations` column, which replaces a whole type's
 * link set per product — this job must ACCUMULATE rows for the same
 * (sku, association_type) pair across a batched file, via the Task-1
 * `upsertLink`/`deleteLink` primitives.
 */
function createBundleKitTypeWithQuantityField(): int
{
    $repository = app(AssociationTypeRepository::class);

    $type = $repository->create([
        'code'            => 'bundle_kit',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);

    return $type->id;
}

function makeAssociationImporter(string $action): array
{
    $importer = app(Importer::class);

    $jobTrack = JobTrack::factory()->create(['action' => $action]);
    $importer->setImport($jobTrack);
    $importer->setErrorHelper(app(Error::class));

    return [$importer, $jobTrack];
}

describe('ProductAssociation import — valid column names', function () {
    it('includes the permanent columns plus active association type field codes', function () {
        createBundleKitTypeWithQuantityField();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        expect($importer->getValidColumnNames())
            ->toContain('sku', 'association_type', 'related_sku', 'quantity');
    });
});

describe('ProductAssociation import — append accumulates links', function () {
    it('accumulates multiple related_sku rows for the same (sku, association_type) instead of replacing them', function () {
        $typeId = createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $p3 = Product::factory()->create();

        [$importer, $jobTrack] = makeAssociationImporter(Import::ACTION_APPEND);

        $row1 = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku, 'quantity' => '2'];
        $row2 = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p3->sku, 'quantity' => '3'];

        expect($importer->validateRow($row1, 1))->toBeTrue();
        expect($importer->validateRow($row2, 2))->toBeTrue();

        $batch = JobTrackBatch::factory()->create([
            'data'         => [$row1, $row2],
            'job_track_id' => $jobTrack->id,
        ]);

        $importer->importBatch($batch);

        $links = DB::table('product_associations')
            ->where('product_id', $p1->id)
            ->where('association_type_id', $typeId)
            ->get();

        expect($links)->toHaveCount(2);

        $quantities = $links
            ->map(fn ($link) => json_decode($link->additional_data, true)['common']['quantity'] ?? null)
            ->sort()
            ->values()
            ->all();

        expect($quantities)->toBe(['2', '3']);
    });

    it('importing the same row twice updates the same link rather than duplicating it', function () {
        createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        [$importer, $jobTrack] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku, 'quantity' => '5'];

        expect($importer->validateRow($row, 1))->toBeTrue();

        $batch = JobTrackBatch::factory()->create([
            'data'         => [$row, $row],
            'job_track_id' => $jobTrack->id,
        ]);

        $importer->importBatch($batch);

        expect(
            DB::table('product_associations')
                ->where('product_id', $p1->id)
                ->where('related_product_id', $p2->id)
                ->count()
        )->toBe(1);
    });
});

describe('ProductAssociation import — delete mode', function () {
    it('removes a single link without touching the other links of the same type', function () {
        $typeId = createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $p3 = Product::factory()->create();

        [$appendImporter, $appendJobTrack] = makeAssociationImporter(Import::ACTION_APPEND);

        $row1 = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku, 'quantity' => '2'];
        $row2 = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p3->sku, 'quantity' => '3'];

        $appendImporter->validateRow($row1, 1);
        $appendImporter->validateRow($row2, 2);

        $appendBatch = JobTrackBatch::factory()->create([
            'data'         => [$row1, $row2],
            'job_track_id' => $appendJobTrack->id,
        ]);

        $appendImporter->importBatch($appendBatch);

        [$deleteImporter, $deleteJobTrack] = makeAssociationImporter(Import::ACTION_DELETE);

        $deleteRow = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku];

        expect($deleteImporter->validateRow($deleteRow, 1))->toBeTrue();

        $deleteBatch = JobTrackBatch::factory()->create([
            'data'         => [$deleteRow],
            'job_track_id' => $deleteJobTrack->id,
        ]);

        $deleteImporter->importBatch($deleteBatch);

        expect(
            DB::table('product_associations')
                ->where('product_id', $p1->id)
                ->where('association_type_id', $typeId)
                ->count()
        )->toBe(1);

        assertDatabaseHas('product_associations', [
            'product_id'          => $p1->id,
            'association_type_id' => $typeId,
            'related_product_id'  => $p3->id,
        ]);

        assertDatabaseMissing('product_associations', [
            'product_id'          => $p1->id,
            'association_type_id' => $typeId,
            'related_product_id'  => $p2->id,
        ]);
    });
});

describe('ProductAssociation import — validation', function () {
    it('skips a row with a non-numeric quantity value', function () {
        createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku, 'quantity' => 'abc'];

        expect($importer->validateRow($row, 1))->toBeFalse();

        expect(DB::table('product_associations')->where('product_id', $p1->id)->count())->toBe(0);
    });

    it('rejects a self-link where sku equals related_sku', function () {
        createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => $p1->sku, 'quantity' => '1'];

        expect($importer->validateRow($row, 1))->toBeFalse();
    });

    it('rejects an unknown association type code', function () {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => $p1->sku, 'association_type' => 'does_not_exist', 'related_sku' => $p2->sku];

        expect($importer->validateRow($row, 1))->toBeFalse();
    });

    it('rejects an unknown sku', function () {
        createBundleKitTypeWithQuantityField();

        $p2 = Product::factory()->create();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => 'MISSING-SKU', 'association_type' => 'bundle_kit', 'related_sku' => $p2->sku, 'quantity' => '1'];

        expect($importer->validateRow($row, 1))->toBeFalse();
    });

    it('rejects an unknown related_sku', function () {
        createBundleKitTypeWithQuantityField();

        $p1 = Product::factory()->create();

        [$importer] = makeAssociationImporter(Import::ACTION_APPEND);

        $row = ['sku' => $p1->sku, 'association_type' => 'bundle_kit', 'related_sku' => 'MISSING-SKU', 'quantity' => '1'];

        expect($importer->validateRow($row, 1))->toBeFalse();
    });
});
