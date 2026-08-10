<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Forces the grid's DB-only query path. A fixture created inside this
 * test's `DatabaseTransactions` wrapper is never actually committed, so an
 * Elasticsearch index-after-commit hook never fires for it -- the ES path
 * would see only stale, previously-indexed data instead of this fixture.
 */
beforeEach(fn () => config(['elasticsearch.enabled' => false]));

/**
 * Builds a 2-level (color/size) configurable + variant structure with a
 * common `name`-style attribute set only on the root. Globally-unique
 * codes since this suite runs against a live/seeded DB.
 */
function issue1331Fixture(): array
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

function fixtureProductIds(array $fixture): array
{
    return [
        $fixture['configurable']->id,
        $fixture['group']->id,
        $fixture['leaf']->id,
        $fixture['directChild']->id,
    ];
}

/**
 * Drives the grid the same way its controller does (`prepare()` then
 * `formatData()`), scoped to exactly this fixture's rows via `productIds`
 * -- bypassing the shared/seeded catalog's own pagination and sort order
 * entirely, so the assertion never depends on how many other products
 * happen to exist in this environment.
 */
function gridRecordsWithColumns(array $columns, array $productIds): array
{
    request()->replace([
        'managedColumns' => $columns,
        'productIds'     => $productIds,
    ]);

    $grid = app(ProductDataGrid::class);

    $grid->prepare();

    return json_decode(json_encode($grid->formatData()['records']), true);
}

it('shows the root parent\'s common attribute value on a 1-level child in the Product Data Grid', function () {
    $this->loginAsAdmin();

    $fixture = issue1331Fixture();

    $records = gridRecordsWithColumns(['product_id', 'sku', $fixture['commonCode']], fixtureProductIds($fixture));

    $childRecord = collect($records)->firstWhere('product_id', $fixture['directChild']->id);

    expect($childRecord)->not->toBeNull()
        ->and($childRecord[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('shows the root parent\'s common attribute value on the variant group and the 2-level leaf child in the Product Data Grid', function () {
    $this->loginAsAdmin();

    $fixture = issue1331Fixture();

    $records = gridRecordsWithColumns(['product_id', 'sku', $fixture['commonCode']], fixtureProductIds($fixture));

    $groupRecord = collect($records)->firstWhere('product_id', $fixture['group']->id);
    $leafRecord = collect($records)->firstWhere('product_id', $fixture['leaf']->id);

    expect($groupRecord)->not->toBeNull()
        ->and($groupRecord[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE')
        ->and($leafRecord)->not->toBeNull()
        ->and($leafRecord[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('still shows the root parent\'s own common attribute value on itself', function () {
    $this->loginAsAdmin();

    $fixture = issue1331Fixture();

    $records = gridRecordsWithColumns(['product_id', 'sku', $fixture['commonCode']], fixtureProductIds($fixture));

    $parentRecord = collect($records)->firstWhere('product_id', $fixture['configurable']->id);

    expect($parentRecord)->not->toBeNull()
        ->and($parentRecord[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
});

it('never exposes the internal parent_id used to resolve inheritance on a grid record', function () {
    $this->loginAsAdmin();

    $fixture = issue1331Fixture();

    $records = gridRecordsWithColumns(['product_id', 'sku', $fixture['commonCode']], fixtureProductIds($fixture));

    $leafRecord = collect($records)->firstWhere('product_id', $fixture['leaf']->id);

    expect($leafRecord)->not->toBeNull()
        ->and($leafRecord)->not->toHaveKey('parent_id');
});

it('fetches each shared ancestor at most once per page instead of once per row', function () {
    $this->loginAsAdmin();

    $fixture = issue1331Fixture();

    $extraLeaves = collect(range(1, 4))->map(function ($i) use ($fixture) {
        $type = $fixture['configurable']->getTypeInstance();

        return $type->createVariant($fixture['configurable'], $fixture['configurable']->super_attributes, [
            'parent_id' => $fixture['group']->id,
            'sku'       => $fixture['group']->sku.'-extra-'.$i,
            'values'    => ['common' => []],
        ]);
    });

    $grid = app(ProductDataGrid::class);

    request()->replace([
        'managedColumns' => ['product_id', 'sku', $fixture['commonCode']],
        'productIds'     => [...fixtureProductIds($fixture), ...$extraLeaves->pluck('id')->all()],
    ]);

    DB::enableQueryLog();

    $grid->prepare();
    $records = json_decode(json_encode($grid->formatData()['records']), true);

    $ancestorLookups = collect(DB::getQueryLog())->filter(
        fn ($entry) => str_contains($entry['query'], 'products') && str_contains(strtolower($entry['query']), ' in (')
    );

    DB::disableQueryLog();

    foreach ($extraLeaves->pluck('id') as $extraLeafId) {
        $record = collect($records)->firstWhere('product_id', $extraLeafId);

        expect($record)->not->toBeNull()
            ->and($record[$fixture['commonCode']])->toBe('PARENT-NAME-VALUE');
    }

    expect($ancestorLookups->count())->toBeLessThanOrEqual(3);
});
