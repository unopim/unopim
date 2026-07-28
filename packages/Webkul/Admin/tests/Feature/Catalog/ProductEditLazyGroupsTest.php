<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Product\Models\Product;

function familyWithGroups(int $groupCount, int $attributesPerGroup, string $prefix = 'grp'): AttributeFamily
{
    $family = AttributeFamily::factory()->create();

    foreach (range(1, $groupCount) as $position) {
        $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
            'attribute_family_id' => $family->id,
            'attribute_group_id'  => AttributeGroup::factory()->create(['code' => $prefix.'_'.$position])->id,
            'position'            => $position,
        ]);

        foreach (Attribute::factory()->count($attributesPerGroup)->create() as $index => $attribute) {
            DB::table('attribute_group_mappings')->insert([
                'attribute_family_group_id' => $mappingId,
                'attribute_id'              => $attribute->id,
                'position'                  => $index + 1,
            ]);
        }
    }

    return $family;
}

it('renders every group for a family under the threshold', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 200]);

    $family = familyWithGroups(3, 2);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id))->assertOk()->original->getData();

    expect($data['lazyGroups'])->toBeFalse()
        ->and($data['groupAttributes'])->toBeNull()
        ->and($data['activeGroup'])->toBeNull();
});

it('hydrates only the active group for a family over the threshold', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id))->assertOk()->original->getData();

    expect($data['lazyGroups'])->toBeTrue()
        ->and($data['activeGroup']['code'])->toBe('grp_1')
        ->and($data['groupAttributes'])->toHaveCount(3)
        ->and($data['groupPage']['total'])->toBe(4);
});

it('renders the group named by the query parameter', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id).'?group=grp_3')
        ->assertOk()->original->getData();

    expect($data['activeGroup']['code'])->toBe('grp_3')
        ->and(collect($data['groupPage']['groups'])->firstWhere('code', 'grp_3')['active'])->toBeTrue();
});

it('falls back to the first group when the requested group is unknown', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id).'?group=nope')
        ->assertOk()->original->getData();

    expect($data['activeGroup']['code'])->toBe('grp_1');
});

it('does not hydrate more attributes as the family grows', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $small = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => familyWithGroups(4, 3, 'small')->id]);
    $large = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => familyWithGroups(40, 3, 'large')->id]);

    $smallData = $this->get(route('admin.catalog.products.edit', $small->id))->assertOk()->original->getData();
    $largeData = $this->get(route('admin.catalog.products.edit', $large->id))->assertOk()->original->getData();

    expect($largeData['groupAttributes']->count())->toBe($smallData['groupAttributes']->count());
});

it('returns to the submitted group after saving', function () {
    $this->loginAsAdmin();

    $family = familyWithGroups(3, 2, 'save');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => 1,
        'group'  => 'save_2',
        'values' => ['common' => ['sku' => $product->sku]],
    ])->assertRedirectContains('group=save_2');
});

it('omits the group from the redirect when none was submitted', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => familyWithGroups(2, 2, 'nogroup')->id,
    ]);

    $response = $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => 1,
        'values' => ['common' => ['sku' => $product->sku]],
    ]);

    expect($response->headers->get('Location'))->not->toContain('group=');
});

it('shows the group sidebar and only the active group for a large family', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3, 'nav');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $codesByGroup = $family->familyGroups()
        ->orderBy('position')
        ->get()
        ->mapWithKeys(fn ($group): array => [
            $group->code => $family->customAttributesForGroup($group->id)->pluck('code')->all(),
        ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id).'?group=nav_2')
        ->assertOk()
        ->assertSee('v-product-attribute-groups', false)
        ->assertSee('name="group" value="nav_2"', false);

    foreach ($codesByGroup['nav_2'] as $code) {
        $response->assertSee('values[common]['.$code.']', false);
    }

    foreach ($codesByGroup['nav_4'] as $code) {
        $response->assertDontSee('values[common]['.$code.']', false);
    }
});

it('shows no group sidebar for a small family', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 200]);

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => familyWithGroups(3, 2, 'plain')->id,
    ]);

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertDontSee('v-product-attribute-groups', false);
});
