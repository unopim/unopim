<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

/**
 * A common attribute set only on the root parent must still export for a
 * variant group or a child variant, whether the hierarchy is 1-level
 * (Parent -> Child) or 2-level (Parent -> Variant Group -> Child).
 */
function exportInheritanceJobTrack(): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'inherit_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv'],
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

function exportProductRows(array $products): array
{
    $jobTrack = exportInheritanceJobTrack();

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $batch = new JobTrackBatch(['data' => array_map(fn (Product $product) => ['id' => $product->id], $products)]);

    $exporter->prepareProducts($batch, null);

    $buffer->rewind();

    $rows = [];

    while ($buffer->valid()) {
        foreach ($buffer->current() as $row) {
            $rows[] = $row;
        }

        $buffer->next();
    }

    return $rows;
}

/**
 * Builds a 2-level (color/size) configurable + variant structure with a
 * common attribute set only on the root. Globally-unique codes since this
 * suite runs against a live/seeded DB.
 */
function exportInheritanceFixture(): array
{
    $commonCode = 'cmn_'.Str::random(8);
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    Attribute::factory()->create([
        'code'              => $commonCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $commonCode,
        $colorCode,
        $sizeCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'CFG-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    $configurable->values = [
        'common' => [
            'sku'       => $configurable->sku,
            $commonCode => 'PARENT-NAME-VALUE',
        ],
    ];
    $configurable->save();

    $type = $configurable->getTypeInstance();

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$sizeOptionCode,
        'values'    => ['common' => [$sizeCode => $sizeOptionCode]],
    ]);

    $directChild = $type->createVariant($configurable, [$color], [
        'parent_id' => $configurable->id,
        'sku'       => $configurable->sku.'-direct-'.$redOptionCode,
        'values'    => ['common' => [$colorCode => $redOptionCode]],
    ]);

    return [
        'configurable' => $configurable->fresh(),
        'group'        => $group->fresh(),
        'leaf'         => $leaf->fresh(),
        'directChild'  => $directChild->fresh(),
        'commonCode'   => $commonCode,
    ];
}

beforeEach(function () {
    $this->loginAsAdmin();

    $staticInitCache = new ReflectionProperty(Exporter::class, 'staticInitCache');
    $staticInitCache->setAccessible(true);
    $staticInitCache->setValue(null, null);
});

it('exports the root parent\'s common attribute value for a 2-level leaf variant', function () {
    $fixture = exportInheritanceFixture();

    $rows = exportProductRows([$fixture['leaf']]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('exports the root parent\'s common attribute value for a variant group', function () {
    $fixture = exportInheritanceFixture();

    $rows = exportProductRows([$fixture['group']]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('exports the root parent\'s common attribute value for a 1-level direct child', function () {
    $fixture = exportInheritanceFixture();

    $rows = exportProductRows([$fixture['directChild']]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('exports the root parent\'s own common attribute value for itself', function () {
    $fixture = exportInheritanceFixture();

    $rows = exportProductRows([$fixture['configurable']]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('exports every row correctly when a variant group and its leaf child are in the same batch', function () {
    $fixture = exportInheritanceFixture();

    $rows = exportProductRows([$fixture['group'], $fixture['leaf']]);

    expect($rows)->toHaveCount(2);

    foreach ($rows as $row) {
        expect($row[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
    }
});
