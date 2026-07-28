<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

/**
 * Plan 4, Task 5: the product exporter's legacy `up_sells`/`cross_sells`/
 * `related_products` SKU-list columns are now gated behind an opt-in
 * `with_associations` filter (default OFF), so the product export stays
 * clean by default while the dedicated association export job (Task 4)
 * remains the rich path for association data.
 *
 * `prepareProducts()` streams each row straight into `$this->exportBuffer`
 * via `write()`. A real `JSONFileBuffer` (the same buffer class the real
 * export pipeline wires in `AbstractExporter::exportData()`) is used here
 * instead of a bespoke spy, so this exercises the exporter's actual write
 * path. Because the CSV header is later built from `array_keys()` of the
 * first written row (`FlatItemBuffer::buildHeaders()`), asserting on the
 * row's keys is equivalent to asserting on the header.
 */
function productExportAssociationJobTrack(array $filters): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'assoc_flag_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => array_merge(['file_format' => 'Csv'], $filters),
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);
}

function exportProductRow(Product $product, array $filters): array
{
    $jobTrack = productExportAssociationJobTrack($filters);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $batch = new JobTrackBatch(['data' => [['id' => $product->id]]]);

    $exporter->prepareProducts($batch, null);

    $buffer->rewind();

    $writtenRow = $buffer->current();

    expect($writtenRow)->not->toBeNull();

    // prepareProducts() writes `[$row]` (one row wrapped in an array) per call.
    return $writtenRow[0];
}

describe('Product export "with_associations" opt-in flag', function () {
    beforeEach(function () {
        $this->loginAsAdmin();

        $familyId = AttributeFamily::where('code', 'default')->value('id')
            ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create()->id;

        $this->upSell = Product::factory()->create(['attribute_family_id' => $familyId]);
        $this->crossSell = Product::factory()->create(['attribute_family_id' => $familyId]);
        $this->related = Product::factory()->create(['attribute_family_id' => $familyId]);

        $sku = 'ASSOC-FLAG-'.uniqid();

        $this->product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $familyId,
        ]);

        $this->product->values = [
            'common'       => ['sku' => $sku],
            'associations' => [
                'up_sells'         => [$this->upSell->sku],
                'cross_sells'      => [$this->crossSell->sku],
                'related_products' => [$this->related->sku],
            ],
        ];
        $this->product->save();
    });

    it('omits the association columns entirely when with_associations is off (default)', function () {
        $row = exportProductRow($this->product, []);

        expect($row)->not->toHaveKey('up_sells')
            ->not->toHaveKey('cross_sells')
            ->not->toHaveKey('related_products');
    });

    it('omits the association columns when with_associations is explicitly 0', function () {
        $row = exportProductRow($this->product, ['with_associations' => '0']);

        expect($row)->not->toHaveKey('up_sells')
            ->not->toHaveKey('cross_sells')
            ->not->toHaveKey('related_products');
    });

    it('includes the legacy association SKU-list columns when with_associations is on', function () {
        $row = exportProductRow($this->product, ['with_associations' => '1']);

        expect($row)->toHaveKey('up_sells')
            ->toHaveKey('cross_sells')
            ->toHaveKey('related_products');

        expect($row['up_sells'])->toBe($this->upSell->sku);
        expect($row['cross_sells'])->toBe($this->crossSell->sku);
        expect($row['related_products'])->toBe($this->related->sku);
    });
});
