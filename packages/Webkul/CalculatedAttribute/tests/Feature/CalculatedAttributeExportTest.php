<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

/**
 * The exporter keeps a per-process static cache of the attribute list, and the
 * registry and category resolver keep their own. A test that creates attributes
 * after an earlier export has run would otherwise read a stale snapshot.
 */
function calcResetCaches(): void
{
    (function (): void {
        static::$staticInitCache = null;
    })->bindTo(null, Exporter::class)();

    app(AttributeRegistry::class)->flush();
    app(CategoryPathResolver::class)->flush();
}

/**
 * Export the given products and return the written rows.
 *
 * @param  array<int, Product>  $products
 * @param  array<string, mixed>  $filters
 * @return array<int, array<string, mixed>>
 */
function calcExportRows(array $products, array $filters = []): array
{
    $jobInstance = JobInstances::create([
        'code'                => 'calc_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => array_merge(['file_format' => 'Csv'], $filters),
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
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $exporter->prepareProducts(
        new JobTrackBatch(['data' => array_map(fn (Product $product): array => ['id' => $product->id], $products)]),
        null
    );

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
 * Arbeitskleidung -> Anzug, named in every active locale so the assertion does
 * not depend on which locale the seeded channel happens to use.
 *
 * @return array{0: Category, 1: Category}
 */
function calcCategoryFixture(): array
{
    $root = Category::whereIsRoot()->first() ?? Category::create(['code' => 'root_'.Str::random(6)]);

    $locales = Channel::first()?->locales->pluck('code')->all() ?: [config('app.locale')];

    $makeNames = fn (string $name): array => [
        'locale_specific' => collect($locales)->mapWithKeys(fn ($locale) => [$locale => ['name' => $name]])->all(),
    ];

    $parent = Category::create(['code' => 'arbeitskleidung_'.Str::random(6), 'parent_id' => $root->id]);
    $parent->additional_data = $makeNames('Arbeitskleidung');
    $parent->save();

    $child = Category::create(['code' => 'anzug_'.Str::random(6), 'parent_id' => $parent->id]);
    $child->additional_data = $makeNames('Anzug');
    $child->save();

    return [$parent, $child];
}

/**
 * @param  array<string, mixed>  $values
 */
function calcProduct(array $values): Product
{
    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(6)]);

    $sku = 'CALC-'.Str::random(6);

    $values['common']['sku'] = $sku;

    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    /*
     * `values` is deliberately absent from Product's Fillable list, so mass
     * assignment would drop it without a word. Assign it explicitly.
     */
    $product->values = $values;
    $product->save();

    return $product;
}

it('exports the calculated value in its own column', function (): void {
    $manufacturer = Attribute::factory()->create([
        'code'              => 'mfr_'.Str::random(6),
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $calculatedCode = 'artkey_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => "concat('-', {$manufacturer->code}, sku)",
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $product = calcProduct(['common' => [$manufacturer->code => 'ACME']]);

    calcResetCaches();

    $rows = calcExportRows([$product]);

    expect($rows)->not->toBeEmpty();
    expect($rows[0])->toHaveKey($calculatedCode);
    expect($rows[0][$calculatedCode])->toBe("ACME-{$product->sku}");
});

it('exports the category label path for the Artikelfamilie case', function (): void {
    [$parent, $child] = calcCategoryFixture();

    $calculatedCode = 'famtree_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => "category_label_path('#')",
        'value_per_locale'  => 1,
        'value_per_channel' => 0,
    ]);

    $product = calcProduct([
        'common'     => [],
        'categories' => [$child->code, $parent->code],
    ]);

    calcResetCaches();

    $rows = calcExportRows([$product]);

    expect($rows[0][$calculatedCode])->toBe('Arbeitskleidung#Anzug');
});

it('honours the profile attribute selection', function (): void {
    $calculatedCode = 'sel_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $product = calcProduct(['common' => []]);

    calcResetCaches();

    $selected = calcExportRows([$product], ['attributes' => [$calculatedCode]]);

    expect($selected[0][$calculatedCode])->toBe(strtoupper($product->sku));

    calcResetCaches();

    $excluded = calcExportRows([$product], ['attributes' => ['status']]);

    expect($excluded[0])->not->toHaveKey($calculatedCode);
});

it('exports an empty cell when the source values are missing', function (): void {
    $missingCode = 'miss_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $missingCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $calculatedCode = 'blank_'.Str::random(6);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => "concat('-', {$missingCode})",
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $product = calcProduct(['common' => []]);

    calcResetCaches();

    $rows = calcExportRows([$product]);

    expect($rows[0])->toHaveKey($calculatedCode);
    expect($rows[0][$calculatedCode])->toBe('');
});
