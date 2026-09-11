<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

/**
 * Resets the Product exporter's static init cache so each test rebuilds the
 * channel/locale/currency scope from a fresh database state.
 */
function resetProductExporterCache(): void
{
    $property = new ReflectionProperty(Exporter::class, 'staticInitCache');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

function readExporterProperty(Exporter $exporter, string $name): mixed
{
    $property = new ReflectionProperty($exporter, $name);
    $property->setAccessible(true);

    return $property->getValue($exporter);
}

function makeProductScopeExporter(array $filters): Exporter
{
    $jobInstance = JobInstances::create([
        'code'                => 'product_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => array_merge(['file_format' => 'Csv'], $filters),
    ]);

    $jobTrack = JobTrack::create([
        'state'               => 'pending',
        'type'                => $jobInstance->type,
        'action'              => $jobInstance->action,
        'validation_strategy' => $jobInstance->validation_strategy,
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);

    return $exporter;
}

function makeInitializedProductExporter(array $filters): Exporter
{
    $exporter = makeProductScopeExporter($filters);

    resetProductExporterCache();
    $exporter->initilize();

    return $exporter;
}

function captureProductScopeRows(Exporter $exporter, Product $product): array
{
    $buffer = new class
    {
        public array $rows = [];

        public function write(array $items, array $options = []): void
        {
            $this->rows = array_merge($this->rows, $items);
        }
    };

    $exporter->setExportBuffer($buffer);
    $exporter->initilize();
    $exporter->prepareProducts(new JobTrackBatch(['data' => [['id' => $product->id]]]), null);

    return $buffer->rows;
}

function seedProductScopeChannels(): void
{
    $enUS = Locale::updateOrCreate(['code' => 'en_US'], ['status' => 1]);
    $frFR = Locale::updateOrCreate(['code' => 'fr_FR'], ['status' => 1]);
    $deDE = Locale::updateOrCreate(['code' => 'de_DE'], ['status' => 1]);

    $usd = Currency::updateOrCreate(['code' => 'USD'], ['status' => 1]);
    $eur = Currency::updateOrCreate(['code' => 'EUR'], ['status' => 1]);
    $gbp = Currency::updateOrCreate(['code' => 'GBP'], ['status' => 1]);

    $web = Channel::factory()->create(['code' => 'web']);
    $web->locales()->sync([$enUS->id, $frFR->id]);
    $web->currencies()->sync([$usd->id, $eur->id]);

    $mobile = Channel::factory()->create(['code' => 'mobile']);
    $mobile->locales()->sync([$deDE->id]);
    $mobile->currencies()->sync([$gbp->id]);

    // The prettus repository cache survives the test transaction; flush it so
    // reads reflect the freshly seeded channels rather than stale instances.
    Cache::flush();
}

beforeEach(fn () => resetProductExporterCache());

afterEach(fn () => resetProductExporterCache());

it('restricts locales and currencies to the selected channel', function () {
    seedProductScopeChannels();

    $exporter = makeInitializedProductExporter(['channels' => ['web']]);

    $channelsAndLocales = readExporterProperty($exporter, 'channelsAndLocales');
    $currencies = readExporterProperty($exporter, 'currencies');

    expect(array_keys($channelsAndLocales))->toBe(['web']);
    expect($channelsAndLocales['web'])->toContain('en_US')->toContain('fr_FR')->not->toContain('de_DE');
    expect($currencies)->toContain('USD')->toContain('EUR')->not->toContain('GBP');
});

it('keeps the full scope when no channel is selected', function () {
    seedProductScopeChannels();

    $exporter = makeInitializedProductExporter([]);

    $channelsAndLocales = readExporterProperty($exporter, 'channelsAndLocales');
    $currencies = readExporterProperty($exporter, 'currencies');

    expect(array_keys($channelsAndLocales))->toContain('web')->toContain('mobile');
    expect($currencies)->toContain('USD')->toContain('EUR')->toContain('GBP');
});

it('intersects explicit locale and currency selections within the channel scope', function () {
    seedProductScopeChannels();

    $exporter = makeInitializedProductExporter([
        'channels'   => ['web'],
        'locales'    => ['en_US'],
        'currencies' => ['EUR'],
    ]);

    $channelsAndLocales = readExporterProperty($exporter, 'channelsAndLocales');
    $currencies = readExporterProperty($exporter, 'currencies');

    expect($channelsAndLocales['web'])->toBe(['en_US']);
    expect($currencies)->toBe(['EUR']);
});

it('keeps every attribute loaded and records the selected attributes', function () {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_attr']);
    Cache::flush();

    $exporter = makeInitializedProductExporter(['attributes' => ['scoped_attr']]);

    $codes = collect(readExporterProperty($exporter, 'attributes'))->map(fn ($attribute) => $attribute->code)->all();

    expect($codes)->toContain('scoped_attr');
    expect(count($codes))->toBeGreaterThan(1);
    expect(readExporterProperty($exporter, 'selectedAttributeCodes'))->toBe(['scoped_attr']);
});

it('drops the columns of attributes outside the selection', function () {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_attr']);
    Cache::flush();

    $exporter = makeInitializedProductExporter(['attributes' => ['scoped_attr']]);

    $method = new ReflectionMethod($exporter, 'setAttributesValues');
    $method->setAccessible(true);

    $values = $method->invoke($exporter, [
        'scoped_attr' => 'selected-value',
        'name'        => 'hidden-value',
    ], null);

    expect($values['scoped_attr'])->toBe('selected-value');
    expect($values)->not->toHaveKey('name');
});

it('drops the price columns of an unselected price attribute', function (): void {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_attr']);
    Attribute::factory()->create(['code' => 'list_price', 'type' => 'price']);
    Cache::flush();

    $exporter = makeInitializedProductExporter(['attributes' => ['scoped_attr']]);

    $method = new ReflectionMethod($exporter, 'setAttributesValues');
    $method->setAccessible(true);

    $values = $method->invoke($exporter, ['scoped_attr' => 'selected-value'], null);

    expect(array_keys($values))->toBe(['scoped_attr']);
});

it('sizes the disk-budget estimate on the columns written before initialization', function (array $filters, int $expectedColumns): void {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_text', 'type' => 'text']);
    Attribute::factory()->create(['code' => 'scoped_price', 'type' => 'price']);
    Attribute::factory()->create(['code' => 'scoped_measurement', 'type' => 'measurement']);
    Channel::where('code', 'mobile')->firstOrFail()->currencies()->attach(
        Currency::where('code', 'EUR')->firstOrFail()->id
    );
    $product = Product::factory()->withInitialValues()->create();
    Cache::flush();

    $exporter = makeProductScopeExporter(array_merge(['channels' => ['web']], $filters));
    $countColumns = new ReflectionMethod($exporter, 'countExportedColumns');

    $estimatedColumns = $countColumns->invoke($exporter);
    $rows = captureProductScopeRows($exporter, $product);

    expect($rows)->not->toBeEmpty();
    expect($rows)->each->toHaveCount($expectedColumns);
    expect($estimatedColumns)->toBe($expectedColumns);
})->with([
    'fixed columns'                  => [['attributes' => ['scoped_text']], 11],
    'association columns'            => [['attributes' => ['scoped_text'], 'with_associations' => true], 14],
    'sku and status counted once'    => [['attributes' => ['sku', 'status', 'scoped_text']], 11],
    'measurement unit column'        => [['attributes' => ['scoped_measurement']], 12],
    'channel currencies'             => [['attributes' => ['scoped_price']], 12],
    'selected currency'              => [['attributes' => ['scoped_price'], 'currencies' => ['EUR']], 11],
    'currency outside channel scope' => [['attributes' => ['scoped_price'], 'currencies' => ['GBP']], 10],
    'shared currency counted once'   => [['attributes' => ['scoped_price'], 'channels' => ['web', 'mobile']], 13],
    'mixed attribute types'          => [['attributes' => ['scoped_text', 'scoped_price', 'scoped_measurement'], 'with_associations' => true], 18],
    'deleted attribute selection'    => [['attributes' => ['deleted_attribute']], 10],
    'selection across query chunks'  => [['attributes' => array_merge(
        array_map(fn (int $index): string => 'deleted_attribute_'.$index, range(1, 999)),
        ['scoped_text', 'scoped_price']
    )], 13],
]);

it('counts additional columns supplied by exporter extensions', function (bool $withAssociations, int $expectedColumns): void {
    app()->bind(Exporter::class, ProductScopeAdditionalColumnsExporter::class);
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_text', 'type' => 'text']);
    $product = Product::factory()->withInitialValues()->create();
    Cache::flush();

    $exporter = makeProductScopeExporter([
        'channels'          => ['web'],
        'attributes'        => ['scoped_text'],
        'with_associations' => $withAssociations,
    ]);
    $estimatedColumns = (new ReflectionMethod($exporter, 'countExportedColumns'))->invoke($exporter);
    $rows = captureProductScopeRows($exporter, $product);

    expect($rows)->not->toBeEmpty();
    expect($rows)->each->toHaveCount($expectedColumns);
    expect($rows[0]['external_id'])->toBe('external-1');
    expect(array_key_exists('custom_association', $rows[0]))->toBe($withAssociations);
    expect($estimatedColumns)->toBe($expectedColumns);
})->with([
    'base fields'        => [false, 12],
    'association fields' => [true, 16],
]);

it('preserves the fixed column order and values for existing exports', function (): void {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_text', 'type' => 'text']);
    $family = AttributeFamily::factory()->create(['code' => 'column_family']);
    $parent = Product::factory()->create(['sku' => 'PARENT-COLUMNS', 'type' => 'configurable']);
    $product = Product::factory()->create([
        'sku'                 => 'CHILD-COLUMNS',
        'status'              => false,
        'parent_id'           => $parent->id,
        'attribute_family_id' => $family->id,
        'values'              => [
            'common'     => ['scoped_text' => 'selected-value'],
            'categories' => ['clothes'],
        ],
    ]);
    Cache::flush();

    $exporter = makeProductScopeExporter([
        'channels'   => ['web'],
        'locales'    => ['en_US'],
        'attributes' => ['scoped_text'],
    ]);

    expect(captureProductScopeRows($exporter, $product))->toBe([[
        'channel'                 => 'web',
        'locale'                  => 'en_US',
        'sku'                     => 'CHILD-COLUMNS',
        'status'                  => 'false',
        'type'                    => 'simple',
        'parent'                  => 'PARENT-COLUMNS',
        'attribute_family'        => 'column_family',
        'variant_structure'       => null,
        'configurable_attributes' => '',
        'categories'              => 'clothes',
        'scoped_text'             => 'selected-value',
    ]]);
});

it('counts every exported attribute column when the selection is empty', function (): void {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_text', 'type' => 'text']);
    Attribute::factory()->create(['code' => 'scoped_price', 'type' => 'price']);
    Attribute::factory()->create(['code' => 'scoped_measurement', 'type' => 'measurement']);
    Cache::flush();

    $exporter = makeProductScopeExporter(['channels' => ['web']]);
    $countColumns = new ReflectionMethod($exporter, 'countExportedColumns');
    $estimatedColumns = $countColumns->invoke($exporter);

    $exporter->initilize();
    $attributeColumns = (new ReflectionMethod($exporter, 'setAttributesValues'))->invoke($exporter, [], null);

    expect($attributeColumns)->toHaveKeys(['scoped_text', 'scoped_price (USD)', 'scoped_price (EUR)', 'scoped_measurement', 'scoped_measurement(unit)'])
        ->not->toHaveKeys(['sku', 'status']);
    expect($estimatedColumns)->toBe(10 + count($attributeColumns));
});

it('rejects an export when its fixed columns push it over the disk budget', function (): void {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_text', 'type' => 'text']);
    Cache::flush();

    $exporter = makeProductScopeExporter(['attributes' => ['scoped_text']]);
    $columns = (new ReflectionMethod($exporter, 'countExportedColumns'))->invoke($exporter);
    $exceedsBudget = new ReflectionMethod($exporter, 'exportExceedsDiskBudget');

    expect($exceedsBudget->invoke($exporter, 3_000_000, $columns, 1_000_000_000))->toBeTrue();
    expect($exceedsBudget->invoke($exporter, 1_000_000, $columns, 1_000_000_000))->toBeFalse();
});

it('keeps every attribute when none is selected', function () {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_attr']);
    Cache::flush();

    $exporter = makeInitializedProductExporter([]);

    $codes = collect(readExporterProperty($exporter, 'attributes'))->map(fn ($attribute) => $attribute->code)->all();

    expect($codes)->toContain('scoped_attr');
    expect(count($codes))->toBeGreaterThan(1);
    expect(readExporterProperty($exporter, 'selectedAttributeCodes'))->toBe([]);
});

it('streams each product row to the export buffer separately to bound memory', function () {
    seedProductScopeChannels();

    $family = AttributeFamily::factory()->create(['code' => 'stream_fam']);

    $skus = ['STREAM-1', 'STREAM-2'];
    $ids = [];

    foreach ($skus as $sku) {
        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = ['common' => ['sku' => $sku]];
        $product->save();

        $ids[] = $product->id;
    }

    Cache::flush();

    // Scope to the seeded 'web' channel so the channel-locale expansion is deterministic rather
    // than picking up every channel already present in the database.
    $exporter = makeInitializedProductExporter(['channels' => ['web']]);

    // Isolate the streaming behaviour from the catalog's attribute volume: with no attribute
    // columns each row stays tiny, so the assertions measure flush granularity, not row width
    // (and the test never approaches the memory limit the way the unbounded export did).
    $meta = new ReflectionProperty($exporter, 'attributeMeta');
    $meta->setAccessible(true);
    $meta->setValue($exporter, []);

    // One row is produced per product per channel-locale pair.
    $channelsAndLocales = readExporterProperty($exporter, 'channelsAndLocales');
    $pairCount = collect($channelsAndLocales)->sum(fn ($locales) => count($locales));
    $expectedRows = count($skus) * $pairCount;

    // A buffer spy that records the rows passed to each write() call so we can prove the
    // exporter streams one row at a time rather than buffering the whole batch in memory.
    $buffer = new class
    {
        public array $writes = [];

        public function write($item, array $options = [])
        {
            $this->writes[] = $item;
        }
    };

    $exporter->setExportBuffer($buffer);

    $batch = new JobTrackBatch([
        'data' => array_map(fn ($id) => ['id' => $id], $ids),
    ]);

    $exporter->prepareProducts($batch, null);

    // Each product × channel-locale row is streamed in its own write() call, so peak memory
    // never holds more than a single row — not the whole batch.
    expect($buffer->writes)->toHaveCount($expectedRows);

    foreach ($buffer->writes as $write) {
        // Each write carries exactly one row, wrapped to keep the buffer's array-of-rows contract.
        expect($write)->toHaveCount(1);
    }

    // Every row is still produced — streaming changes when rows flush, not which rows.
    $allRows = collect($buffer->writes)->flatten(1);

    expect($allRows)->toHaveCount($expectedRows);
    expect($allRows->pluck('sku')->unique()->values()->all())
        ->toEqualCanonicalizing($skus);
});

it('flags an export whose estimated buffer exceeds the disk budget', function () {
    $exporter = app(Exporter::class);
    $method = new ReflectionMethod($exporter, 'exportExceedsDiskBudget');
    $method->setAccessible(true);

    $free = 1_000_000_000; // 1 GB free → budget = 0.8 GB; bytes/cell = 32

    // 30M cells × 32 = 960 MB > 800 MB budget → blocked
    expect($method->invoke($exporter, 30_000_000, 1, $free))->toBeTrue();

    // 10M cells × 32 = 320 MB < 800 MB budget → allowed
    expect($method->invoke($exporter, 10_000_000, 1, $free))->toBeFalse();
});

it('counts channel-locale pairs honouring the channel and locale filters', function () {
    seedProductScopeChannels();

    $webOnly = makeInitializedProductExporter(['channels' => ['web']]);
    $method = new ReflectionMethod($webOnly, 'countChannelLocalePairs');
    $method->setAccessible(true);

    // web is seeded with en_US + fr_FR
    expect($method->invoke($webOnly))->toBe(2);

    $webEnOnly = makeInitializedProductExporter(['channels' => ['web'], 'locales' => ['en_US']]);

    expect($method->invoke($webEnOnly))->toBe(1);
});

it('parses every stored multiselect shape into codes', function () {
    $stored = json_encode([
        ['code' => 'en_US', 'label' => 'English (United States)'],
        ['code' => 'fr_FR', 'label' => 'French (France)'],
    ]);

    expect(ScopeFilterValue::toCodes($stored))->toBe(['en_US', 'fr_FR']);
    expect(ScopeFilterValue::toCodes('USD,EUR'))->toBe(['USD', 'EUR']);
    expect(ScopeFilterValue::toCodes(['GBP']))->toBe(['GBP']);
    expect(ScopeFilterValue::toCodes(null))->toBe([]);
});

class ProductScopeAdditionalColumnsExporter extends Exporter
{
    protected function getBaseFields(array $rowData = []): array
    {
        return array_merge(parent::getBaseFields($rowData), ['external_id' => 'external-1']);
    }

    protected function getAssociationColumns(): array
    {
        return [...parent::getAssociationColumns(), 'custom_association'];
    }
}
