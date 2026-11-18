<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\VariantStructureWriter;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();
});

/**
 * Family with one select axis and a one-level structure built on it.
 *
 * @return array{0: AttributeFamily, 1: VariantStructure, 2: Attribute}
 */
function vsGuardFamilyWithStructure(): array
{
    $axis = Attribute::factory()->create(['code' => 'vsg_color_'.Str::random(8), 'type' => 'select']);

    $factory = AttributeFamily::factory();

    $family = $factory->create(['code' => 'vsg_fam_'.Str::random(8)]);

    $factory->linkAttributeGroupToFamily($family);

    $family->refresh();

    $factory->linkAttributesToFamily($family, $axis);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vsg_vs_'.Str::random(8),
        'name'                => 'In use',
        'levels'              => 1,
    ]);

    VariantStructureAxis::create([
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $axis->id,
        'level'                => 'level_1',
        'position'             => 0,
    ]);

    return [$family, $structure, $axis];
}

/**
 * Configurable product that points at the structure.
 */
function vsGuardProductUsing(AttributeFamily $family, VariantStructure $structure): Product
{
    return app(ProductRepository::class)->create([
        'sku'                  => 'vsg-config-'.Str::random(8),
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);
}

/**
 * Bulk-save entry for a one-level structure on the given axis.
 *
 * @return array{code: string, name: string, levels: int, axes: array{level_1: array<int, string>, level_2: array<int, string>}, placements: array{common: array<int, string>, sub_parent: array<int, string>, variant: array<int, string>}}
 */
function vsGuardBulkPayload(VariantStructure $structure, string $axisCode, string $name): array
{
    return [
        'code'       => $structure->code,
        'name'       => $name,
        'levels'     => 1,
        'axes'       => ['level_1' => [$axisCode], 'level_2' => []],
        'placements' => ['common' => [], 'sub_parent' => [], 'variant' => []],
    ];
}

/**
 * Extra select attribute linked to the family, usable as another axis.
 */
function vsGuardSecondAxis(AttributeFamily $family): Attribute
{
    $axis = Attribute::factory()->create(['code' => 'vsg_size_'.Str::random(8), 'type' => 'select']);

    AttributeFamily::factory()->linkAttributesToFamily($family, $axis);

    return $axis;
}

/**
 * Configurable product on the structure with one child variant.
 */
function vsGuardProductWithVariant(AttributeFamily $family, VariantStructure $structure): Product
{
    $configurable = vsGuardProductUsing($family, $structure);

    Product::factory()->create([
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $family->id,
    ]);

    return $configurable;
}

/**
 * Attribute ids of the structure's axes in level and position order.
 *
 * @return array<int, int>
 */
function vsGuardAxisIds(VariantStructure $structure): array
{
    return VariantStructureAxis::where('variant_structure_id', $structure->id)
        ->orderBy('level')
        ->orderBy('position')
        ->pluck('attribute_id')
        ->all();
}

/**
 * Lower-cased SQL of every query the callback runs, in order.
 *
 * @return array<int, string>
 */
function vsGuardQueriesDuring(callable $callback): array
{
    $queries = [];
    $listening = true;

    DB::listen(function (QueryExecuted $query) use (&$queries, &$listening): void {
        if ($listening) {
            $queries[] = strtolower($query->sql);
        }
    });

    $callback();

    $listening = false;

    return $queries;
}

/**
 * Position of the first query reading the table, optionally only a row-locking one
 * or one whose SQL also contains the given fragment.
 *
 * @param  array<int, string>  $queries
 */
function vsGuardQueryIndex(array $queries, string $table, bool $forUpdate, string $contains = ''): ?int
{
    foreach ($queries as $index => $sql) {
        if (! preg_match('/from [`"]?\w*'.$table.'[`"]?\s/', $sql)) {
            continue;
        }

        if ($forUpdate !== str_contains($sql, 'for update')) {
            continue;
        }

        if ($contains === '' || str_contains($sql, $contains)) {
            return $index;
        }
    }

    return null;
}

it('refuses to delete a variant structure that a product still uses', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $product = vsGuardProductUsing($family, $structure);

    $this->deleteJson(route('admin.catalog.families.variant-structures.delete', [$family->id, $structure->id]))
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::app.catalog.families.edit.variant-structure-in-use'));

    expect(VariantStructure::find($structure->id))->not->toBeNull()
        ->and(VariantStructureAxis::where('variant_structure_id', $structure->id)->count())->toBe(1)
        ->and($product->refresh()->variant_structure_id)->toBe($structure->id);
});

it('deletes a variant structure that no product uses together with its axes', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $this->deleteJson(route('admin.catalog.families.variant-structures.delete', [$family->id, $structure->id]))
        ->assertOk();

    expect(VariantStructure::find($structure->id))->toBeNull()
        ->and(VariantStructureAxis::where('variant_structure_id', $structure->id)->count())->toBe(0);
});

it('keeps the same structure row when the bulk save resubmits an in-use structure', function (): void {
    [$family, $structure, $axis] = vsGuardFamilyWithStructure();

    $product = vsGuardProductUsing($family, $structure);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [vsGuardBulkPayload($structure, $axis->code, 'Renamed')],
    ])->assertOk();

    expect($structure->refresh()->name)->toBe('Renamed')
        ->and($product->refresh()->variant_structure_id)->toBe($structure->id)
        ->and(VariantStructure::where('attribute_family_id', $family->id)->count())->toBe(1);
});

it('refuses a bulk save that drops an in-use structure and leaves the family untouched', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $product = vsGuardProductUsing($family, $structure);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [],
    ])->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::app.catalog.families.edit.variant-structure-in-use'));

    expect(VariantStructure::find($structure->id))->not->toBeNull()
        ->and($product->refresh()->variant_structure_id)->toBe($structure->id);
});

it('drops an unused structure that the bulk save no longer lists', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [],
    ])->assertOk();

    expect(VariantStructure::find($structure->id))->toBeNull();
});

it('keeps the listed in-use structure and drops the unlisted unused one in the same bulk save', function (): void {
    [$family, $kept, $axis] = vsGuardFamilyWithStructure();

    $product = vsGuardProductUsing($family, $kept);

    $dropped = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vsg_unused_'.Str::random(8),
        'levels'              => 1,
    ]);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [vsGuardBulkPayload($kept, $axis->code, 'Kept')],
    ])->assertOk();

    expect(VariantStructure::find($dropped->id))->toBeNull()
        ->and(VariantStructure::find($kept->id)?->name)->toBe('Kept')
        ->and($product->refresh()->variant_structure_id)->toBe($kept->id);
});

it('refuses to change the axes of a structure whose product has variants through the single save', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $otherAxis = vsGuardSecondAxis($family);

    vsGuardProductWithVariant($family, $structure);

    $axesBefore = vsGuardAxisIds($structure);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structure' => ['id' => $structure->id] + vsGuardBulkPayload($structure, $otherAxis->code, 'Changed axes'),
    ])->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::app.catalog.families.edit.variant-structure-locked'));

    expect(vsGuardAxisIds($structure))->toBe($axesBefore)
        ->and($structure->refresh()->name)->toBe('In use');
});

it('refuses to change the levels of a structure whose product has variants through the bulk save', function (): void {
    [$family, $structure, $axis] = vsGuardFamilyWithStructure();

    $otherAxis = vsGuardSecondAxis($family);

    vsGuardProductWithVariant($family, $structure);

    $axesBefore = vsGuardAxisIds($structure);

    $payload = [
        'levels' => 2,
        'axes'   => ['level_1' => [$axis->code], 'level_2' => [$otherAxis->code]],
    ] + vsGuardBulkPayload($structure, $axis->code, 'Two levels');

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [$payload],
    ])->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::app.catalog.families.edit.variant-structure-locked'));

    expect((int) $structure->refresh()->levels)->toBe(1)
        ->and($structure->name)->toBe('In use')
        ->and(vsGuardAxisIds($structure))->toBe($axesBefore);
});

it('still lets the bulk save rename a structure whose product has variants', function (): void {
    [$family, $structure, $axis] = vsGuardFamilyWithStructure();

    $product = vsGuardProductWithVariant($family, $structure);

    $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [vsGuardBulkPayload($structure, $axis->code, 'Renamed with variants')],
    ])->assertOk();

    expect($structure->refresh()->name)->toBe('Renamed with variants')
        ->and($product->refresh()->variant_structure_id)->toBe($structure->id);
});

it('locks the structure before a locking reference check when deleting it', function (): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $queries = vsGuardQueriesDuring(fn () => $this->deleteJson(route('admin.catalog.families.variant-structures.delete', [$family->id, $structure->id]))
        ->assertOk());

    $structureLock = vsGuardQueryIndex($queries, 'variant_structures', true);
    $referenceCheck = vsGuardQueryIndex($queries, 'products', true);

    expect($structureLock)->not->toBeNull()
        ->and($referenceCheck)->not->toBeNull()
        ->and($structureLock)->toBeLessThan($referenceCheck);
});

it('locks every structure the bulk save drops before the first reference check', function (): void {
    [$family, $first] = vsGuardFamilyWithStructure();

    $second = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vsg_unused_'.Str::random(8),
        'levels'              => 1,
    ]);

    $queries = vsGuardQueriesDuring(fn () => $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [],
    ])->assertOk());

    $firstReferenceCheck = vsGuardQueryIndex($queries, 'products', true);
    $locksAfterCheck = array_filter(
        array_slice($queries, (int) $firstReferenceCheck),
        fn (string $sql): bool => vsGuardQueryIndex([$sql], 'variant_structures', true) !== null
    );

    expect($firstReferenceCheck)->not->toBeNull()
        ->and($locksAfterCheck)->toBe([])
        ->and(VariantStructure::whereKey([$first->id, $second->id])->exists())->toBeFalse();
});

it('checks for variants only after locking the structure', function (string $mode): void {
    [$family, $structure] = vsGuardFamilyWithStructure();

    $otherAxis = vsGuardSecondAxis($family);

    vsGuardProductWithVariant($family, $structure);

    $payload = vsGuardBulkPayload($structure, $otherAxis->code, 'Changed axes');
    $body = $mode === 'single' ? ['structure' => ['id' => $structure->id] + $payload] : ['structures' => [$payload]];

    $queries = vsGuardQueriesDuring(fn () => $this->putJson(route('admin.catalog.families.variant-structures.save', $family->id), $body)
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('admin::app.catalog.families.edit.variant-structure-locked')));

    $structureLock = vsGuardQueryIndex($queries, 'variant_structures', true);
    $variantCheck = vsGuardQueryIndex($queries, 'products', false, 'parent_id');

    expect($structureLock)->not->toBeNull()
        ->and($variantCheck)->not->toBeNull()
        ->and($structureLock)->toBeLessThan($variantCheck);
})->with(['single', 'bulk']);

it('rolls back the axis and placement rows when writing them fails part way', function (): void {
    [$family, $structure, $axis] = vsGuardFamilyWithStructure();

    $placed = vsGuardSecondAxis($family);

    VariantStructureAttribute::create([
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $placed->id,
        'level'                => 'common',
    ]);

    $familyAttributes = $family->customAttributes()->get()->keyBy('code');

    expect(fn () => app(VariantStructureWriter::class)->writeRows(
        $structure,
        1,
        ['level_1' => ['vsg_missing_'.Str::random(8)]],
        [],
        $familyAttributes
    ))->toThrow(ErrorException::class);

    expect(vsGuardAxisIds($structure))->toBe([$axis->id])
        ->and(VariantStructureAttribute::where('variant_structure_id', $structure->id)->pluck('attribute_id')->all())->toBe([$placed->id]);
});
