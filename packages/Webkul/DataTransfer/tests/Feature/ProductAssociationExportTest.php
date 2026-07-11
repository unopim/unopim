<?php

use Webkul\DataTransfer\Helpers\Exporters\ProductAssociation\Exporter;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;

/**
 * Row-per-link association EXPORT job (Plan 4, Task 4): the export
 * counterpart to the Task-3 row-per-link association IMPORT job. Each
 * exported row is `sku,association_type,related_sku` plus one column per
 * active, non-locale association type field code (e.g. `quantity`), read
 * from the link's `additional_data['common']`.
 *
 * Column names MUST match the importer's expected columns exactly so a
 * file exported here re-imports cleanly.
 */
function createBundleKitTypeForExportTest(): int
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

/**
 * Build an Exporter instance with `source` wired exactly as the real
 * export pipeline wires it (`Export::getTypeExporter()->setSource($this->source)`
 * in `Webkul\DataTransfer\Helpers\Export`), i.e. the configured
 * `ProductAssociationRepository` from `exporters.php`.
 */
function makeAssociationExporter(): Exporter
{
    $exporter = app(Exporter::class);
    $exporter->setSource(app(ProductAssociationRepository::class));

    return $exporter;
}

/**
 * Invoke the protected `getResults()` join query directly, the way the
 * real pipeline's `initializeBatches()` does internally.
 */
function callAssociationExporterGetResults(Exporter $exporter)
{
    $method = new ReflectionMethod($exporter, 'getResults');
    $method->setAccessible(true);

    return $method->invoke($exporter);
}

/**
 * Round-trips the joined rows through a real `JobTrackBatch` model so the
 * `data` attribute's `array` cast (de)serializes exactly like the real
 * export pipeline: `initializeBatches()` persists `getResults()` rows to
 * the DB (JSON-encoding each row, including nested `additional_data`), and
 * `exportBatch()` later reads them back already decoded to plain arrays.
 */
function roundTripAssociationBatch(iterable $results): JobTrackBatch
{
    return new JobTrackBatch(['data' => iterator_to_array($results)]);
}

describe('ProductAssociation export — join query', function () {
    it('produces one row per link with correct sku, association_type, related_sku, and quantity', function () {
        $typeId = createBundleKitTypeForExportTest();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $p3 = Product::factory()->create();

        $associationRepository = app(ProductAssociationRepository::class);
        $associationRepository->upsertLink($p1->id, $typeId, $p2->id, null, ['common' => ['quantity' => '2']]);
        $associationRepository->upsertLink($p1->id, $typeId, $p3->id, null, ['common' => ['quantity' => '3']]);

        $exporter = makeAssociationExporter();

        $results = callAssociationExporterGetResults($exporter);
        $batch = roundTripAssociationBatch($results);

        $rows = $exporter->prepareAssociations($batch);

        expect($rows)->toHaveCount(2);

        $rowsBySku = collect($rows)->keyBy('related_sku');

        expect($rowsBySku->has($p2->sku))->toBeTrue()
            ->and($rowsBySku->has($p3->sku))->toBeTrue();

        $rowToP2 = $rowsBySku->get($p2->sku);
        expect($rowToP2['sku'])->toBe($p1->sku)
            ->and($rowToP2['association_type'])->toBe('bundle_kit')
            ->and($rowToP2['related_sku'])->toBe($p2->sku)
            ->and($rowToP2['quantity'])->toBe('2');

        $rowToP3 = $rowsBySku->get($p3->sku);
        expect($rowToP3['sku'])->toBe($p1->sku)
            ->and($rowToP3['association_type'])->toBe('bundle_kit')
            ->and($rowToP3['related_sku'])->toBe($p3->sku)
            ->and($rowToP3['quantity'])->toBe('3');
    });

    it('produces rows whose keys match the importer\'s expected columns exactly (round-trip)', function () {
        $typeId = createBundleKitTypeForExportTest();

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        app(ProductAssociationRepository::class)->upsertLink($p1->id, $typeId, $p2->id, null, ['common' => ['quantity' => '9']]);

        $exporter = makeAssociationExporter();
        $batch = roundTripAssociationBatch(callAssociationExporterGetResults($exporter));

        $rows = $exporter->prepareAssociations($batch);

        // The permanent columns plus the dynamic `quantity` field must be present;
        // additional, unrelated field codes from other active association types
        // (union across all types) may also legitimately appear.
        expect($rows[0])->toHaveKeys(['sku', 'association_type', 'related_sku', 'quantity']);
    });

    it('gives every row the same set of column keys (sparse union across association types)', function () {
        $bundleTypeId = createBundleKitTypeForExportTest();

        $otherType = app(AssociationTypeRepository::class)->create([
            'code'            => 'accessory_of',
            'status'          => 1,
            'position'        => 2,
            'is_user_defined' => 1,
            'en_US'           => ['name' => 'Accessory Of'],
            'fields'          => [
                [
                    'code'        => 'note',
                    'type'        => 'text',
                    'is_required' => 0,
                    'status'      => 1,
                    'section'     => 'left',
                    'en_US'       => ['name' => 'Note'],
                ],
            ],
        ]);

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $p3 = Product::factory()->create();

        $associationRepository = app(ProductAssociationRepository::class);
        $associationRepository->upsertLink($p1->id, $bundleTypeId, $p2->id, null, ['common' => ['quantity' => '4']]);
        $associationRepository->upsertLink($p1->id, $otherType->id, $p3->id, null, ['common' => ['note' => 'spare part']]);

        $exporter = makeAssociationExporter();
        $batch = roundTripAssociationBatch(callAssociationExporterGetResults($exporter));

        $rows = $exporter->prepareAssociations($batch);

        expect($rows)->toHaveCount(2);

        foreach ($rows as $row) {
            expect($row)->toHaveKeys(['sku', 'association_type', 'related_sku', 'quantity', 'note']);
        }

        $bundleRow = collect($rows)->firstWhere('association_type', 'bundle_kit');
        $accessoryRow = collect($rows)->firstWhere('association_type', 'accessory_of');

        expect($bundleRow['quantity'])->toBe('4')
            ->and($bundleRow['note'])->toBeNull();

        expect($accessoryRow['note'])->toBe('spare part')
            ->and($accessoryRow['quantity'])->toBeNull();
    });

    it('excludes locale-specific fields from the exported columns', function () {
        $repository = app(AssociationTypeRepository::class);

        $type = $repository->create([
            'code'            => 'kit_with_locale_export',
            'status'          => 1,
            'position'        => 1,
            'is_user_defined' => 1,
            'en_US'           => ['name' => 'Kit With Locale Export'],
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
                [
                    'code'             => 'locale_note',
                    'type'             => 'text',
                    'is_required'      => 0,
                    'value_per_locale' => 1,
                    'status'           => 1,
                    'section'          => 'left',
                    'en_US'            => ['name' => 'Locale Note'],
                ],
            ],
        ]);

        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();

        app(ProductAssociationRepository::class)->upsertLink($p1->id, $type->id, $p2->id, null, [
            'common'          => ['quantity' => '1'],
            'locale_specific' => ['en_US' => ['locale_note' => 'should not be exported']],
        ]);

        $exporter = makeAssociationExporter();
        $batch = roundTripAssociationBatch(callAssociationExporterGetResults($exporter));

        $rows = $exporter->prepareAssociations($batch);

        expect($rows[0])->not->toHaveKey('locale_note');
    });
});

describe('ProductAssociation export — dynamic field codes', function () {
    it('unions non-locale field codes across all active association types', function () {
        createBundleKitTypeForExportTest();

        $exporter = makeAssociationExporter();

        $ref = new ReflectionMethod($exporter, 'getNonLocaleFieldCodes');
        $ref->setAccessible(true);
        $codes = $ref->invoke($exporter);

        expect($codes)->toContain('quantity');
    });
});
