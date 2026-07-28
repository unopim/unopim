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

function groupIdOf(AttributeFamily $family, string $code): int
{
    return (int) $family->groupSummaryByCode('en_US', $code)->id;
}

it('renders every group at once for a family under the threshold', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 200]);

    $family = familyWithGroups(3, 2);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id))->assertOk()->original->getData();

    expect($data['lazyGroups'])->toBeFalse()
        ->and($data['renderGroups'])->toHaveCount(3)
        ->and($data['nextGroupId'])->toBeNull();
});

it('renders only the first group for a family over the threshold', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id))->assertOk()->original->getData();

    expect($data['lazyGroups'])->toBeTrue()
        ->and($data['renderGroups'])->toHaveCount(1)
        ->and($data['renderGroups']->first()->code)->toBe('grp_1')
        ->and($data['groupAttributes'][$data['renderGroups']->first()->id])->toHaveCount(3)
        ->and($data['nextGroupId'])->toBe(groupIdOf($family, 'grp_2'));
});

it('starts from the group named by the query parameter', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id).'?group=grp_3')
        ->assertOk()->original->getData();

    expect($data['renderGroups']->first()->code)->toBe('grp_3')
        ->and($data['nextGroupId'])->toBe(groupIdOf($family, 'grp_4'));
});

it('falls back to the first group when the requested group is unknown', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3);

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id).'?group=nope')
        ->assertOk()->original->getData();

    expect($data['renderGroups']->first()->code)->toBe('grp_1');
});

it('stops pointing at a next group once the last one is reached', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 1]);

    $family = familyWithGroups(2, 2, 'last');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $data = $this->get(route('admin.catalog.products.edit', $product->id).'?group=last_2')
        ->assertOk()->original->getData();

    expect($data['nextGroupId'])->toBeNull();
});

it('does not hydrate more attributes as the family grows', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $small = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => familyWithGroups(4, 3, 'small')->id]);
    $large = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => familyWithGroups(40, 3, 'large')->id]);

    $smallData = $this->get(route('admin.catalog.products.edit', $small->id))->assertOk()->original->getData();
    $largeData = $this->get(route('admin.catalog.products.edit', $large->id))->assertOk()->original->getData();

    $rendered = fn (array $data): int => collect($data['groupAttributes'])->sum(fn ($attributes) => $attributes->count());

    expect($rendered($largeData))->toBe($rendered($smallData));
});

it('renders the scroll loader and only the first group of a large family', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 5]);

    $family = familyWithGroups(4, 3, 'page');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $codesByGroup = $family->familyGroups()
        ->orderBy('position')
        ->get()
        ->mapWithKeys(fn ($group): array => [
            $group->code => $family->customAttributesForGroup($group->id)->pluck('code')->all(),
        ]);

    $response = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSee('v-product-attribute-group-loader', false)
        ->assertSee('data-attribute-group="page_1"', false);

    foreach ($codesByGroup['page_1'] as $code) {
        $response->assertSee('values[common]['.$code.']', false);
    }

    foreach ($codesByGroup['page_4'] as $code) {
        $response->assertDontSee('values[common]['.$code.']', false);
    }
});

it('does not render the scroll loader for a small family', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 200]);

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => familyWithGroups(3, 2, 'plain')->id,
    ]);

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertDontSee('v-product-attribute-group-loader', false);
});

it('serves a group of fields with a pointer to the next group', function () {
    $this->loginAsAdmin();

    $family = familyWithGroups(3, 2, 'fields');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $codes = $family->customAttributesForGroup(groupIdOf($family, 'fields_2'))->pluck('code')->all();

    $response = $this->getJson(route('admin.catalog.products.attribute_group_fields', [
        'id'      => $product->id,
        'groupId' => groupIdOf($family, 'fields_2'),
    ]))->assertOk()->assertJsonPath('nextGroupId', groupIdOf($family, 'fields_3'));

    $html = $response->json('html');

    expect($html)->toContain('data-attribute-group="fields_2"');

    foreach ($codes as $code) {
        expect($html)->toContain('values[common]['.$code.']');
    }
});

it('reports no next group for the last group of fields', function () {
    $this->loginAsAdmin();

    $family = familyWithGroups(2, 2, 'tail');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $this->getJson(route('admin.catalog.products.attribute_group_fields', [
        'id'      => $product->id,
        'groupId' => groupIdOf($family, 'tail_2'),
    ]))->assertOk()->assertJsonPath('nextGroupId', null);
});

it('carries the component registrations an appended group needs', function () {
    $this->loginAsAdmin();

    $family = familyWithGroups(2, 2, 'scripts');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $html = $this->getJson(route('admin.catalog.products.attribute_group_fields', [
        'id'      => $product->id,
        'groupId' => groupIdOf($family, 'scripts_1'),
    ]))->assertOk()->json('html');

    expect($html)->toContain("app.component('v-accordion'")
        ->and($html)->toContain('id="v-accordion-template"');
});

it('404s for a group outside the product family', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => familyWithGroups(1, 1, 'own')->id,
    ]);

    $foreign = AttributeGroup::factory()->create();

    $this->getJson(route('admin.catalog.products.attribute_group_fields', [
        'id'      => $product->id,
        'groupId' => $foreign->id,
    ]))->assertNotFound();
});

it('refuses an unauthenticated request for a group of fields', function () {
    $family = familyWithGroups(1, 1, 'guest');

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $response = $this->getJson(route('admin.catalog.products.attribute_group_fields', [
        'id'      => $product->id,
        'groupId' => groupIdOf($family, 'guest_1'),
    ]));

    expect($response->status())->toBeIn([401, 302]);
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

it('never renders the same attribute twice across groups', function () {
    $this->loginAsAdmin();

    config(['product_editor.lazy_group_threshold' => 200]);

    $family = AttributeFamily::factory()->create();

    $shared = Attribute::factory()->create();

    foreach ([1, 2] as $position) {
        $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
            'attribute_family_id' => $family->id,
            'attribute_group_id'  => AttributeGroup::factory()->create(['code' => 'shared_'.$position])->id,
            'position'            => $position,
        ]);

        DB::table('attribute_group_mappings')->insert([
            'attribute_family_group_id' => $mappingId,
            'attribute_id'              => $shared->id,
            'position'                  => 1,
        ]);
    }

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))->assertOk()->getContent();

    expect(substr_count($content, 'name="values[common]['.$shared->code.']"'))->toBe(1);
});

it('rejects a save that leaves a required attribute of an unloaded group empty', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $filled = Attribute::factory()->create(['type' => 'text']);
    $requiredElsewhere = Attribute::factory()->create(['type' => 'text', 'is_required' => 1]);

    $groups = [];

    foreach ([[1, $filled], [2, $requiredElsewhere]] as [$position, $attribute]) {
        $group = AttributeGroup::factory()->create(['code' => 'req_'.$position]);

        $groups[$position] = $group;

        $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
            'attribute_family_id' => $family->id,
            'attribute_group_id'  => $group->id,
            'position'            => $position,
        ]);

        DB::table('attribute_group_mappings')->insert([
            'attribute_family_group_id' => $mappingId,
            'attribute_id'              => $attribute->id,
            'position'                  => 1,
        ]);
    }

    $product = Product::factory()->create(['type' => 'simple', 'attribute_family_id' => $family->id]);

    $field = 'values[common]['.$requiredElsewhere->code.']';

    $this->putJson(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => 1,
        'values' => ['common' => ['sku' => $product->sku, $filled->code => 'filled']],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.'.$field, trans('validation.required', ['attribute' => $requiredElsewhere->name ?: $requiredElsewhere->code]))
        ->assertJsonPath('errorGroups.'.$field, $groups[2]->id);
});

it('accepts a partial save once every required attribute holds a value', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $required = Attribute::factory()->create(['type' => 'text', 'is_required' => 1]);

    $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
        'attribute_family_id' => $family->id,
        'attribute_group_id'  => AttributeGroup::factory()->create(['code' => 'ok_1'])->id,
        'position'            => 1,
    ]);

    DB::table('attribute_group_mappings')->insert([
        'attribute_family_group_id' => $mappingId,
        'attribute_id'              => $required->id,
        'position'                  => 1,
    ]);

    $product = Product::factory()->create([
        'type'                => 'simple',
        'attribute_family_id' => $family->id,
        'values'              => ['common' => [$required->code => 'already saved']],
    ]);

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'status' => 1,
        'values' => ['common' => ['sku' => $product->sku]],
    ])->assertRedirect();
});
