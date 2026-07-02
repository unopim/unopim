<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

function pipelineProduct(string $sku, int $familyId, int $status = 1): Product
{
    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => $status,
        'attribute_family_id' => $familyId,
    ]);

    $product->values = ['common' => ['sku' => $sku]];
    $product->save();

    return $product;
}

/**
 * Drives the real export cursor (ProductSource picks the SQL cursor while
 * Elasticsearch is disabled) and returns the matched skus.
 */
function cursorSkus(array $filters): array
{
    $cursor = app(ProductSource::class)->getResults(['filters' => $filters], app(ProductRepository::class), 100);

    $ids = [];

    for ($cursor->rewind(); $cursor->valid(); $cursor->next()) {
        $ids[] = $cursor->current()['id'];
    }

    return Product::whereIn('id', $ids)->pluck('sku')->all();
}

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->family = AttributeFamily::factory()->create(['code' => 'pipeline_fam']);
    $this->other = AttributeFamily::factory()->create(['code' => 'pipeline_other']);

    $this->match = pipelineProduct('PIPE-MATCH', $this->family->id);
    $this->miss = pipelineProduct('PIPE-MISS', $this->other->id);

    Cache::flush();
});

it('applies the family filter through the real product export cursor', function () {
    $skus = cursorSkus(['attribute_families' => ['pipeline_fam']]);

    expect($skus)->toContain('PIPE-MATCH')->not->toContain('PIPE-MISS');
});

it('applies a status filter through the real product export cursor', function () {
    Product::where('id', $this->miss->id)->update(['status' => 0]);

    $skus = cursorSkus(['status' => 'enable']);

    expect($skus)->toContain('PIPE-MATCH')->not->toContain('PIPE-MISS');
});

it('applies the time condition through the exporter getResults pipeline', function () {
    Product::where('id', $this->match->id)->update(['updated_at' => now()->subDay()]);
    Product::where('id', $this->miss->id)->update(['updated_at' => now()->subDays(40)]);

    $jobInstance = JobInstances::create([
        'code'                => 'pipeline_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv', 'time_condition' => 'last_n_days', 'time_value' => 7],
    ]);

    $jobTrack = JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->setSource(app(ProductRepository::class));

    $method = new ReflectionMethod($exporter, 'getResults');
    $method->setAccessible(true);
    $cursor = $method->invoke($exporter);

    $ids = [];

    for ($cursor->rewind(); $cursor->valid(); $cursor->next()) {
        $ids[] = $cursor->current()['id'];
    }

    $skus = Product::whereIn('id', $ids)->pluck('sku')->all();

    expect($skus)->toContain('PIPE-MATCH')->not->toContain('PIPE-MISS');
});
