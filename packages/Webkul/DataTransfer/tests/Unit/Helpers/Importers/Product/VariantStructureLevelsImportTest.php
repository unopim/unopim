<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Database\Factories\AttributeFamilyFactory;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Isolated family with two select axes and a color (and, for two levels, size) variant structure.
 *
 * @return array{family: string, structure: VariantStructure, color: string, size: string, colors: array<int, string>, sizes: array<int, string>}
 */
function levelsImportFixture(int $levels): array
{
    $color = Attribute::factory()->create(['code' => 'lvl_color_'.Str::lower(Str::random(8)), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'lvl_size_'.Str::lower(Str::random(8)), 'type' => 'select']);

    $family = AttributeFamily::factory()->withMinimalAttributesForProductTypes($color)->create(['status' => 1]);
    (new AttributeFamilyFactory)->linkAttributesToFamily($family->refresh(), $size);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'by_color_size_'.Str::lower(Str::random(8)),
        'name'                => 'By color and size',
        'levels'              => $levels,
    ]);

    $axes = [['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0]];

    if ($levels === 2) {
        $axes[] = ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0];
    }

    VariantStructureAxis::insert($axes);

    return [
        'family'    => $family->code,
        'structure' => $structure,
        'color'     => $color->code,
        'size'      => $size->code,
        'colors'    => $color->options()->pluck('code')->all(),
        'sizes'     => $size->options()->pluck('code')->all(),
    ];
}

/**
 * CSV header for the levels import fixtures.
 *
 * @param  array{family: string, structure: VariantStructure, color: string, size: string, colors: array<int, string>, sizes: array<int, string>}  $fixture
 */
function levelsImportHeader(array $fixture, string $structureColumn = 'variant_structure'): string
{
    return implode(',', ['channel', 'locale', 'sku', 'status', 'type', 'parent', 'attribute_family', $structureColumn, $fixture['color'], $fixture['size']]);
}

/**
 * CSV row for the levels import fixtures.
 *
 * @param  array{family: string, structure: VariantStructure, color: string, size: string, colors: array<int, string>, sizes: array<int, string>}  $fixture
 */
function levelsImportRow(array $fixture, string $sku, string $type, string $parent = '', string $structure = '', string $color = '', string $size = ''): string
{
    return implode(',', ['default', 'en_US', $sku, 'true', $type, $parent, $fixture['family'], $structure, $color, $size]);
}

/**
 * Import error expected for a variant_group row under the given single-level structure.
 */
function levelsMismatchMessage(VariantStructure $structure): string
{
    return trans('data_transfer::app.importers.products.validation.errors.variant-structure-levels-mismatch', ['code' => $structure->code]);
}

/**
 * Import error expected for a simple row directly under the given two-level structure.
 */
function levelsTwoLevelMismatchMessage(VariantStructure $structure): string
{
    return trans('data_transfer::app.importers.products.validation.errors.variant-structure-two-levels-mismatch', ['code' => $structure->code]);
}

/**
 * Run an append import of the given CSV lines through the queued job.
 */
function runLevelsImport(string $header, array $rows, string $strategy = Import::VALIDATION_STRATEGY_STOP_ON_ERROR): JobTrack
{
    $allowedErrors = $strategy === Import::VALIDATION_STRATEGY_SKIP_ERRORS ? 10 : 0;
    $filePath = 'imports/variant-structure-levels-'.Str::random(8).'.csv';

    Storage::disk('private')->put($filePath, implode("\n", array_merge([$header], $rows))."\n");

    $jobInstance = JobInstances::create([
        'code'                => 'variant_structure_levels_'.Str::random(8),
        'entity_type'         => 'products',
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => $strategy,
        'allowed_errors'      => $allowedErrors,
        'field_separator'     => ',',
        'file_path'           => $filePath,
    ]);

    $jobTrack = JobTrack::create([
        'type'                => 'import',
        'state'               => 'pending',
        'validation_strategy' => $strategy,
        'allowed_errors'      => $allowedErrors,
        'field_separator'     => ',',
        'file_path'           => $filePath,
        'meta'                => $jobInstance->toArray(),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => auth()->guard('admin')->id(),
        'action'              => 'append',
    ]);

    ImportTrackBatch::dispatchSync($jobTrack);

    Storage::disk('private')->delete($filePath);

    return $jobTrack->refresh();
}

beforeEach(function (): void {
    $this->loginAsAdmin();
});

it('rejects variant_group rows under a single-level variant structure', function (): void {
    $fixture = levelsImportFixture(1);
    [$black] = $fixture['colors'];
    [$medium] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black', 'variant_group', $sku, color: $black),
        levelsImportRow($fixture, $sku.'-black-m', 'simple', $sku.'-black', color: $black, size: $medium),
    ]);

    expect($jobTrack->state)->toBe(Import::STATE_FAILED)
        ->and($jobTrack->completed_at)->not->toBeNull()
        ->and($jobTrack->errors)->toContain('Row(s) 2: '.levelsMismatchMessage($fixture['structure']))
        ->and(Product::whereIn('sku', [$sku, $sku.'-black', $sku.'-black-m'])->exists())->toBeFalse();
});

it('rejects a variant_group row under an existing configurable with a single-level structure', function (): void {
    $fixture = levelsImportFixture(1);
    [$black] = $fixture['colors'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    app(ProductRepository::class)->create([
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $fixture['structure']->attribute_family_id,
        'sku'                  => $sku,
        'variant_structure_id' => $fixture['structure']->id,
        'super_attributes'     => [$fixture['color']],
    ]);

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku.'-black', 'variant_group', $sku, color: $black),
    ]);

    expect($jobTrack->errors)->toContain('Row(s) 1: '.levelsMismatchMessage($fixture['structure']))
        ->and(Product::where('sku', $sku.'-black')->exists())->toBeFalse();
});

it('keeps the configurable and skips the variant_group branch when errors are skipped', function (): void {
    $fixture = levelsImportFixture(1);
    [$black, $white] = $fixture['colors'];
    [$medium] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black', 'variant_group', $sku, color: $black),
        levelsImportRow($fixture, $sku.'-black-m', 'simple', $sku.'-black', color: $black, size: $medium),
        levelsImportRow($fixture, $sku.'-white', 'simple', $sku, color: $white),
    ], Import::VALIDATION_STRATEGY_SKIP_ERRORS);

    expect($jobTrack->state)->toBe('completed')
        ->and($jobTrack->errors)->toContain('Row(s) 2: '.levelsMismatchMessage($fixture['structure']))
        ->and(Product::where('sku', $sku)->exists())->toBeTrue()
        ->and(Product::where('sku', $sku.'-white')->first()?->parent?->sku)->toBe($sku)
        ->and(Product::whereIn('sku', [$sku.'-black', $sku.'-black-m'])->exists())->toBeFalse();
});

it('imports a two-level hierarchy under a two-level variant structure with its axis values', function (): void {
    $fixture = levelsImportFixture(2);
    [$black] = $fixture['colors'];
    [$medium] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black', 'variant_group', $sku, color: $black),
        levelsImportRow($fixture, $sku.'-black-m', 'simple', $sku.'-black', size: $medium),
    ]);

    $group = Product::where('sku', $sku.'-black')->first();
    $simple = Product::where('sku', $sku.'-black-m')->first();

    expect($jobTrack->state)->toBe('completed')
        ->and($jobTrack->errors_count)->toBe(0)
        ->and($group?->parent?->sku)->toBe($sku)
        ->and($group?->values['common'][$fixture['color']] ?? null)->toBe($black)
        ->and($simple?->parent?->sku)->toBe($sku.'-black')
        ->and($simple?->values['common'][$fixture['size']] ?? null)->toBe($medium);
});

it('imports a simple variant under an existing variant_group of a two-level structure', function (): void {
    $fixture = levelsImportFixture(2);
    [$black] = $fixture['colors'];
    [, $large] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black', 'variant_group', $sku, color: $black),
    ]);

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku.'-black-l', 'simple', $sku.'-black', size: $large),
    ]);

    $simple = Product::where('sku', $sku.'-black-l')->first();

    expect($jobTrack->state)->toBe('completed')
        ->and($jobTrack->errors_count)->toBe(0)
        ->and($simple?->parent?->sku)->toBe($sku.'-black')
        ->and($simple?->values['common'][$fixture['size']] ?? null)->toBe($large);
});

it('rejects a simple row directly under a configurable with a two-level structure', function (): void {
    $fixture = levelsImportFixture(2);
    [$black] = $fixture['colors'];
    [$medium] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black-m', 'simple', $sku, color: $black, size: $medium),
    ]);

    expect($jobTrack->state)->toBe(Import::STATE_FAILED)
        ->and($jobTrack->errors)->toContain('Row(s) 2: '.levelsTwoLevelMismatchMessage($fixture['structure']))
        ->and(Product::whereIn('sku', [$sku, $sku.'-black-m'])->exists())->toBeFalse();
});

it('rejects a simple row under an existing configurable with a two-level structure', function (): void {
    $fixture = levelsImportFixture(2);
    [$black] = $fixture['colors'];
    [$medium] = $fixture['sizes'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    app(ProductRepository::class)->create([
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $fixture['structure']->attribute_family_id,
        'sku'                  => $sku,
        'variant_structure_id' => $fixture['structure']->id,
        'super_attributes'     => [$fixture['color'], $fixture['size']],
    ]);

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku.'-black-m', 'simple', $sku, color: $black, size: $medium),
    ]);

    expect($jobTrack->errors)->toContain('Row(s) 1: '.levelsTwoLevelMismatchMessage($fixture['structure']))
        ->and(Product::where('sku', $sku.'-black-m')->exists())->toBeFalse();
});

it('imports simple variants directly under a single-level variant structure', function (): void {
    $fixture = levelsImportFixture(1);
    [$black] = $fixture['colors'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['structure']->code),
        levelsImportRow($fixture, $sku.'-black', 'simple', $sku, color: $black),
    ]);

    $simple = Product::where('sku', $sku.'-black')->first();

    expect($jobTrack->state)->toBe('completed')
        ->and($jobTrack->errors_count)->toBe(0)
        ->and($simple?->parent?->sku)->toBe($sku)
        ->and($simple?->values['common'][$fixture['color']] ?? null)->toBe($black);
});

it('still imports a legacy configurable without a variant structure', function (): void {
    $fixture = levelsImportFixture(1);
    [$red] = $fixture['colors'];
    $sku = 'levels-'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture, 'configurable_attributes'), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $fixture['color']),
        levelsImportRow($fixture, $sku.'-red', 'simple', $sku, color: $red),
    ]);

    expect($jobTrack->state)->toBe('completed')
        ->and($jobTrack->errors_count)->toBe(0)
        ->and(Product::where('sku', $sku.'-red')->first()?->parent?->sku)->toBe($sku);
});

it('names the unknown variant structure code in the import error', function (): void {
    $fixture = levelsImportFixture(1);
    $sku = 'levels-'.Str::lower(Str::random(8));
    $unknownCode = 'missing_structure_'.Str::lower(Str::random(8));

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), [
        levelsImportRow($fixture, $sku, 'configurable', structure: $unknownCode),
    ]);

    $message = trans('data_transfer::app.importers.products.validation.errors.variant-structure-not-found', ['code' => $unknownCode]);

    expect($jobTrack->state)->toBe(Import::STATE_FAILED)
        ->and($jobTrack->errors)->toContain('Row(s) 1: '.$message)
        ->and($message)->not->toContain(':code')
        ->and(Product::where('sku', $sku)->exists())->toBeFalse();
});

it('completes a header-only file once with a completion time and no completed event', function (): void {
    $fixture = levelsImportFixture(1);

    Event::fake(['data_transfer.imports.completed']);

    $jobTrack = runLevelsImport(levelsImportHeader($fixture), []);

    expect($jobTrack->state)->toBe(Import::STATE_COMPLETED)
        ->and($jobTrack->completed_at)->not->toBeNull()
        ->and($jobTrack->errors_count)->toBe(0)
        ->and($jobTrack->batches)->toHaveCount(0)
        ->and($jobTrack->summary)->toEqual(['created' => 0, 'updated' => 0, 'deleted' => 0]);

    Event::assertNotDispatched('data_transfer.imports.completed');
});
