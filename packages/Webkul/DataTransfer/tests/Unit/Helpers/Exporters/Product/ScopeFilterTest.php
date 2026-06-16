<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

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

function makeInitializedProductExporter(array $filters): Exporter
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

    resetProductExporterCache();
    $exporter->initilize();

    return $exporter;
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

it('keeps every attribute column and records the selected attributes', function () {
    seedProductScopeChannels();
    Attribute::factory()->create(['code' => 'scoped_attr']);
    Cache::flush();

    $exporter = makeInitializedProductExporter(['attributes' => ['scoped_attr']]);

    $codes = collect(readExporterProperty($exporter, 'attributes'))->map(fn ($attribute) => $attribute->code)->all();

    expect($codes)->toContain('scoped_attr');
    expect(count($codes))->toBeGreaterThan(1);
    expect(readExporterProperty($exporter, 'selectedAttributeCodes'))->toBe(['scoped_attr']);
});

it('exports values only for the selected attributes while keeping every column', function () {
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
    expect($values)->toHaveKey('name');
    expect($values['name'])->toBeNull();
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
