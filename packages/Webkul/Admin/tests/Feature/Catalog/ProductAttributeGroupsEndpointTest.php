<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Product\Models\Product;

function productWithNamedGroups(array $codes): Product
{
    $family = AttributeFamily::factory()->create();

    $attribute = Attribute::factory()->create();

    foreach ($codes as $position => $code) {
        $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
            'attribute_family_id' => $family->id,
            'attribute_group_id'  => AttributeGroup::factory()->create(['code' => $code])->id,
            'position'            => $position + 1,
        ]);

        DB::table('attribute_group_mappings')->insert([
            'attribute_family_group_id' => $mappingId,
            'attribute_id'              => $attribute->id,
            'position'                  => 1,
        ]);
    }

    return Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);
}

it('serves a page of groups with attribute counts', function () {
    $this->loginAsAdmin();

    config(['product_editor.groups_per_page' => 2]);

    $product = productWithNamedGroups(['alpha', 'beta', 'gamma']);

    $this->getJson(route('admin.catalog.products.attribute_groups', $product->id))
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonPath('lastPage', 2)
        ->assertJsonCount(2, 'groups')
        ->assertJsonPath('groups.0.code', 'alpha')
        ->assertJsonPath('groups.0.attributesCount', 1);
});

it('serves later pages', function () {
    $this->loginAsAdmin();

    config(['product_editor.groups_per_page' => 2]);

    $product = productWithNamedGroups(['alpha', 'beta', 'gamma']);

    $this->getJson(route('admin.catalog.products.attribute_groups', $product->id).'?page=2')
        ->assertOk()
        ->assertJsonCount(1, 'groups')
        ->assertJsonPath('groups.0.code', 'gamma');
});

it('filters groups by search term', function () {
    $this->loginAsAdmin();

    $product = productWithNamedGroups(['alpha', 'beta', 'gamma']);

    $this->getJson(route('admin.catalog.products.attribute_groups', $product->id).'?query=gam')
        ->assertOk()
        ->assertJsonCount(1, 'groups')
        ->assertJsonPath('groups.0.code', 'gamma');
});

it('flags the active group', function () {
    $this->loginAsAdmin();

    $product = productWithNamedGroups(['alpha', 'beta']);

    $activeId = $product->attribute_family->groupSummaryByCode('en_US', 'beta')->id;

    $this->getJson(route('admin.catalog.products.attribute_groups', $product->id).'?active='.$activeId)
        ->assertOk()
        ->assertJsonPath('groups.1.active', true)
        ->assertJsonPath('groups.0.active', false);
});

it('rejects an invalid page', function () {
    $this->loginAsAdmin();

    $product = productWithNamedGroups(['alpha']);

    $this->getJson(route('admin.catalog.products.attribute_groups', $product->id).'?page=0')
        ->assertUnprocessable();
});

it('404s for an unknown product', function () {
    $this->loginAsAdmin();

    $this->getJson(route('admin.catalog.products.attribute_groups', 99999999))->assertNotFound();
});

it('refuses an unauthenticated request', function () {
    $product = productWithNamedGroups(['alpha']);

    $response = $this->getJson(route('admin.catalog.products.attribute_groups', $product->id));

    expect($response->status())->toBeIn([401, 302]);
});
