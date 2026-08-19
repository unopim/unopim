<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\DataTransfer\Jobs\System\BulkProductUpdate;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the bulk edit page when products and attributes are in session', function () {
    $products = Product::factory()->count(2)->create();

    $sku = Attribute::where('code', 'sku')->first();
    $name = Attribute::where('code', 'name')->first();

    // Set up session via filters endpoint
    $response = $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $products->pluck('id')->toArray(),
        'filter'  => [
            'filtered_attributes' => [
                ['id' => $sku->id],
                ['id' => $name->id],
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['message', 'redirect']);

    // Now load the bulk edit page
    $this->get(route('admin.catalog.products.bulkedit'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.bulk-edit.action'));
});

it('should return validation error when too many products selected', function () {
    $productIds = range(1, 101);

    $response = $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $productIds,
        'filter'  => [
            'filtered_attributes' => [['id' => 1]],
        ],
    ]);

    $response->assertUnprocessable();
});

it('should redirect when no products are in session', function () {
    $this->get(route('admin.catalog.products.bulkedit'))
        ->assertRedirect();
});

it('should fetch attributes for bulk edit modal', function () {
    $response = $this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'));

    $response->assertOk();
    $response->assertJsonStructure([
        'options',
        'page',
        'lastPage',
    ]);

    // SKU and unsupported types should not appear
    $options = collect($response->json('options'));

    $this->assertTrue($options->where('code', 'sku')->isEmpty(), 'SKU should be excluded from bulk edit attributes');
});

it('fires catalog.product.update.after for every product changed by bulk edit', function () {
    $family = AttributeFamily::find(1)
        ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

    $products = Product::factory()->withInitialValues()->count(2)->create(['attribute_family_id' => $family->id]);

    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    Event::fake(['catalog.product.update.after', 'catalog.product.bulk.edit.after']);

    // Sync queue in the test env runs BulkProductUpdate inline, so the event
    // fires within this request. Payload mirrors what the bulk-edit Vue
    // spreadsheet posts: { product_id: { attribute_code: value } }.
    $payload = [];
    foreach ($products as $product) {
        $payload[$product->id] = [$attribute->code => 'bulk-changed-'.$product->id];
    }

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), ['data' => $payload])
        ->assertOk();

    Event::assertDispatched('catalog.product.update.after', count($products));

    // One bulk event is dispatched carrying all processed product IDs.
    // The payload is ['ids' => [...]], matching how call_user_func_array passes it.
    Event::assertDispatched('catalog.product.bulk.edit.after', function ($event, $payload) use ($products) {
        $ids = $payload['ids'] ?? [];

        return count(array_intersect($products->pluck('id')->toArray(), $ids)) === $products->count();
    });
});

it('does not fire catalog.product.update.after for a no-op bulk edit save', function () {
    $products = Product::factory()->count(2)->create();

    Event::fake(['catalog.product.update.after']);

    // Re-posting each product's own SKU changes nothing, so no update event should fire.
    $payload = [];
    foreach ($products as $product) {
        $payload[$product->id] = ['sku' => $product->sku];
    }

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), ['data' => $payload])
        ->assertOk();

    Event::assertNotDispatched('catalog.product.update.after');
});

it('should fetch only attributes belonging to the selected products families', function () {
    // Helper to create a family with a linked attribute group and attribute
    $makeFamily = function (Attribute $attr): AttributeFamily {
        $group = AttributeGroup::factory()->create();
        $family = AttributeFamily::factory()->create();
        $family->familyGroups()->attach($group);
        $mapping = $family->attributeFamilyGroupMappings()->first();
        $mapping->customAttributes()->attach($attr);

        return $family;
    };

    $attrA = Attribute::factory()->create(['type' => 'text']);
    $familyA = $makeFamily($attrA);

    $attrB = Attribute::factory()->create(['type' => 'text']);
    $familyB = $makeFamily($attrB);

    // Create one product per family
    $productA = Product::factory()->create(['attribute_family_id' => $familyA->id]);
    $productB = Product::factory()->create(['attribute_family_id' => $familyB->id]);

    // Populate session via the filters endpoint (mirrors real usage)
    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$productA->id],
        'filter'  => [],
    ])->assertOk();

    $response = $this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'));
    $response->assertOk();

    $codes = collect($response->json('options'))->pluck('code')->toArray();

    // attrA should appear (it belongs to productA's family)
    expect($codes)->toContain($attrA->code);

    // attrB must NOT appear (it belongs to a different family not selected)
    expect($codes)->not->toContain($attrB->code);
});

it('orders the columns by the order the attributes were selected in, with sku first', function () {
    $product = Product::factory()->create();

    $selected = Attribute::whereIn('code', ['image', 'name', 'description'])->pluck('id')->all();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$product->id],
        'filter'  => [
            'filtered_attributes' => array_map(fn ($id) => ['id' => $id], $selected),
        ],
    ])->assertOk();

    $response = $this->get(route('admin.catalog.products.bulkedit'));
    $response->assertOk();

    $codes = collect($response->original->getData()['columns'])->pluck('code')->all();

    $expected = collect($selected)
        ->map(fn ($id) => Attribute::find($id)->code)
        ->prepend('sku')
        ->all();

    expect($codes)->toBe($expected);
});

it('still offers attributes when the session holds product ids that no longer exist', function () {
    $product = Product::factory()->create();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$product->id],
        'filter'  => [],
    ])->assertOk();

    $product->delete();

    $response = $this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'));
    $response->assertOk();

    expect($response->json('options'))->not->toBeEmpty();
});

it('should display readable channel and locale names in column headers', function () {
    $products = Product::factory()->count(1)->create();

    $nameAttribute = Attribute::where('code', 'name')->first();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $products->pluck('id')->toArray(),
        'filter'  => [
            'filtered_attributes' => [
                ['id' => $nameAttribute->id],
            ],
        ],
    ]);

    $response = $this->get(route('admin.catalog.products.bulkedit'));

    $response->assertOk();

    // If name is locale-specific, the header label should contain the locale name
    // not just the locale code
    if ($nameAttribute->value_per_locale) {
        $locale = core()->getAllActiveLocales()->first();

        if ($locale && $locale->name) {
            $content = $response->getContent();

            // The JSON headers passed to Vue should have locale name, not code
            $this->assertStringContainsString($locale->name, $content);
        }
    }
});

/**
 * Two sibling variant groups under one 2-level (color + brand / size)
 * configurable, plus an attribute the root configurable owns, so a bulk save can
 * be pointed at an ancestor-owned write and at a colliding own-axis rename.
 * Codes are randomised because this suite runs against a live, seeded database.
 */
function bulkSaveVariantFixture(): array
{
    $suffix = Str::random(8);

    $colorCode = 'bscolor_'.$suffix;
    $brandCode = 'bsbrand_'.$suffix;
    $rootNoteCode = 'bsrnote_'.$suffix;

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $brand = Attribute::factory()->create(['code' => $brandCode, 'type' => 'select']);

    $rootNote = Attribute::factory()->create([
        'code'              => $rootNoteCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create(['code' => 'bsfam_'.$suffix]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $colorCode,
        $brandCode,
        $rootNoteCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bsst_'.$suffix,
        'name'                => 'Bulk Save Structure',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $brand->id, 'level' => 'level_1', 'position' => 1],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $rootNote->id, 'level' => 'common'],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'BS-'.$suffix,
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $brandCode],
    ]);

    $configurable->values = [
        'common' => [
            'sku'         => $configurable->sku,
            $rootNoteCode => 'ROOT-OWNED',
        ],
    ];
    $configurable->save();

    $type = $configurable->getTypeInstance();

    $colors = $color->options->pluck('code')->all();
    $brands = $brand->options->pluck('code')->all();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $colors[0],
        'group_values'      => [$brandCode => $brands[0]],
        'sku'               => $configurable->sku.'-G1',
    ]);

    $sibling = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => $colors[1],
        'group_values'      => [$brandCode => $brands[0]],
        'sku'               => $configurable->sku.'-G2',
    ]);

    return [
        'group'        => $group->fresh(),
        'sibling'      => $sibling->fresh(),
        'colorCode'    => $colorCode,
        'rootNoteCode' => $rootNoteCode,
        'colors'       => $colors,
    ];
}

it('rejects a bulk save that changes an ancestor-owned attribute without queueing the job', function () {
    $fixture = bulkSaveVariantFixture();

    Queue::fake();

    $response = $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            $fixture['group']->id => [$fixture['rootNoteCode'] => 'CHILD-OVERRIDE'],
        ],
    ]);

    $response->assertUnprocessable();

    expect(json_encode($response->json('errors')))
        ->toContain($fixture['group']->sku)
        ->toContain($fixture['rootNoteCode']);

    Queue::assertNotPushed(BulkProductUpdate::class);
});

it('rejects a bulk save whose axis rename collides with a sibling without queueing the job', function () {
    $fixture = bulkSaveVariantFixture();

    Queue::fake();

    $response = $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            $fixture['group']->id => [$fixture['colorCode'] => $fixture['colors'][1]],
        ],
    ]);

    $response->assertUnprocessable();

    expect(json_encode($response->json('errors')))->toContain($fixture['group']->sku);

    Queue::assertNotPushed(BulkProductUpdate::class);
});

it('queues the job for a bulk save the variant structure allows', function () {
    $fixture = bulkSaveVariantFixture();

    Queue::fake();

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            $fixture['group']->id => [$fixture['colorCode'] => $fixture['colors'][2]],
        ],
    ])->assertOk();

    Queue::assertPushed(BulkProductUpdate::class);
});

it('rejects the whole bulk save when one product of a mixed payload is invalid', function () {
    $fixture = bulkSaveVariantFixture();

    Queue::fake();

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [
            $fixture['group']->id   => [$fixture['rootNoteCode'] => 'CHILD-OVERRIDE'],
            $fixture['sibling']->id => [$fixture['colorCode'] => $fixture['colors'][2]],
        ],
    ])->assertUnprocessable();

    Queue::assertNotPushed(BulkProductUpdate::class);
});

/**
 * A full three-level tree on one 2-level structure: an axis fixed at each level,
 * one attribute placed on the root and one on the group. Enough for every cell
 * state the grid can render — own, inherited from either ancestor, and owned by
 * a level below — to appear on a real page load.
 */
function bulkEditGridFixture(): array
{
    $suffix = Str::random(8);

    $colorCode = 'begcolor_'.$suffix;
    $sizeCode = 'begsize_'.$suffix;
    $rootCode = 'begroot_'.$suffix;
    $groupCode = 'beggroup_'.$suffix;

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $rootNote = Attribute::factory()->create(['code' => $rootCode, 'type' => 'text']);
    $groupNote = Attribute::factory()->create(['code' => $groupCode, 'type' => 'text']);

    $family = AttributeFamily::factory()->create(['code' => 'begfam_'.$suffix]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $colorCode,
        $sizeCode,
        $rootCode,
        $groupCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'begst_'.$suffix,
        'name'                => 'Bulk Edit Grid Structure',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $rootNote->id, 'level' => 'common'],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $groupNote->id, 'level' => 'sub_parent'],
    ]);

    $colorOption = $color->options->first()->code;
    $sizeOption = $size->options->first()->code;

    $configurable = Product::factory()->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'BEG-'.$suffix,
        'variant_structure_id' => $structure->id,
    ]);

    $configurable->values = ['common' => ['sku' => $configurable->sku, $rootCode => 'ROOT-OWNED']];
    $configurable->save();

    $group = Product::factory()->create([
        'type'                => 'variant_group',
        'attribute_family_id' => $family->id,
        'sku'                 => 'BEG-'.$suffix.'-G1',
        'parent_id'           => $configurable->id,
    ]);

    $group->values = ['common' => ['sku' => $group->sku, $colorCode => $colorOption, $groupCode => 'GROUP-OWNED']];
    $group->save();

    $variant = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => $family->id,
        'sku'                 => 'BEG-'.$suffix.'-G1-S1',
        'parent_id'           => $group->id,
    ]);

    $variant->values = ['common' => ['sku' => $variant->sku, $sizeCode => $sizeOption]];
    $variant->save();

    return [
        'configurable' => $configurable->fresh(),
        'group'        => $group->fresh(),
        'variant'      => $variant->fresh(),
        'colorId'      => $color->id,
        'sizeId'       => $size->id,
        'rootId'       => $rootNote->id,
        'groupId'      => $groupNote->id,
        'colorCode'    => $colorCode,
        'sizeCode'     => $sizeCode,
        'rootCode'     => $rootCode,
        'groupCode'    => $groupCode,
        'colorOption'  => $colorOption,
        'sizeOption'   => $sizeOption,
    ];
}

/**
 * The row models the bulk-edit page hands the Vue grid, keyed by product id.
 * They travel as the escaped JSON of the editor's `:initial-data` binding, so
 * asserting on them is asserting on exactly what the front end receives.
 */
function bulkEditRowsFor(array $fixture): array
{
    test()->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [
            $fixture['configurable']->id,
            $fixture['group']->id,
            $fixture['variant']->id,
        ],
        'filter' => [
            'filtered_attributes' => [
                ['id' => $fixture['colorId']],
                ['id' => $fixture['sizeId']],
                ['id' => $fixture['rootId']],
                ['id' => $fixture['groupId']],
            ],
        ],
    ])->assertOk();

    $html = test()->get(route('admin.catalog.products.bulkedit'))->assertOk()->getContent();

    expect($html)->toMatch('/:initial-data="/');

    preg_match('/:initial-data="([^"]*)"/', $html, $matches);

    $rows = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

    return collect($rows)->keyBy('id')->all();
}

it('locks a cell whose attribute an ancestor owns and fills it with that ancestor value', function () {
    $fixture = bulkEditGridFixture();

    $rows = bulkEditRowsFor($fixture);

    $variantRow = $rows[$fixture['variant']->id];

    expect($variantRow['locks'][$fixture['rootCode']])->toBe('inherited')
        ->and($variantRow['inheritedValues']['common'][$fixture['rootCode']])->toBe('ROOT-OWNED')
        ->and($variantRow['locks'][$fixture['groupCode']])->toBe('inherited')
        ->and($variantRow['inheritedValues']['common'][$fixture['groupCode']])->toBe('GROUP-OWNED');
});

it('fills an inherited axis cell from the level that owns the axis, not from the root', function () {
    $fixture = bulkEditGridFixture();

    $rows = bulkEditRowsFor($fixture);

    $variantRow = $rows[$fixture['variant']->id];

    expect($variantRow['locks'][$fixture['colorCode']])->toBe('inherited')
        ->and($variantRow['inheritedValues']['common'][$fixture['colorCode']])->toBe($fixture['colorOption']);
});

it('locks a cell whose attribute a level below owns and leaves it with no value', function () {
    $fixture = bulkEditGridFixture();

    $rows = bulkEditRowsFor($fixture);

    $groupRow = $rows[$fixture['group']->id];
    $configurableRow = $rows[$fixture['configurable']->id];

    expect($groupRow['locks'][$fixture['sizeCode']])->toBe('na')
        ->and($groupRow['inheritedValues']['common'])->not->toHaveKey($fixture['sizeCode'])
        ->and($configurableRow['locks'][$fixture['colorCode']])->toBe('na')
        ->and($configurableRow['locks'][$fixture['sizeCode']])->toBe('na')
        ->and($configurableRow['inheritedValues']['common'])->toBe([]);
});

it('leaves a cell editable on the node that owns the attribute at its own level', function () {
    $fixture = bulkEditGridFixture();

    $rows = bulkEditRowsFor($fixture);

    expect($rows[$fixture['configurable']->id]['locks'][$fixture['rootCode']])->toBe('own')
        ->and($rows[$fixture['group']->id]['locks'][$fixture['colorCode']])->toBe('own')
        ->and($rows[$fixture['group']->id]['locks'][$fixture['groupCode']])->toBe('own')
        ->and($rows[$fixture['variant']->id]['locks'][$fixture['sizeCode']])->toBe('own');
});

it('reports the axis codes each row may write, matching what the save guard allows', function () {
    $fixture = bulkEditGridFixture();

    $rows = bulkEditRowsFor($fixture);

    expect($rows[$fixture['configurable']->id]['axes'])->toBe([])
        ->and($rows[$fixture['group']->id]['axes'])->toBe([$fixture['colorCode']])
        ->and($rows[$fixture['variant']->id]['axes'])->toBe([$fixture['sizeCode']]);
});

it('offers axis attributes as bulk edit columns because the save guard allows an own level axis write', function () {
    $fixture = bulkEditGridFixture();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$fixture['group']->id],
        'filter'  => [],
    ])->assertOk();

    $codes = collect($this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'))
        ->assertOk()
        ->json('options'))
        ->pluck('code')
        ->all();

    expect($codes)->toContain($fixture['colorCode'])
        ->and($codes)->toContain($fixture['sizeCode']);
});
