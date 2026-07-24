<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

/*
 * Correctness guard for F2: after switching the select/multiselect branch to fetch
 * only the selected options, the pre-selected option's translated label must still
 * render on the product edit page.
 */
it('renders the translated label of a selected select option (F2)', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $group = AttributeGroup::factory()->create();
    $family->familyGroups()->attach($group);

    $mapping = $family->attributeFamilyGroupMappings()->first();

    foreach (Attribute::whereIn('code', ['sku', 'status'])->get() as $position => $attribute) {
        $mapping->customAttributes()->attach($attribute, ['position' => $position + 1]);
    }

    $select = Attribute::factory()->create(['type' => 'select', 'code' => 'perf_colour']);
    $select->translateOrNew('en_US')->name = 'Colour';
    $select->save();

    $chosen = AttributeOption::create(['code' => 'crimson', 'sort_order' => 1, 'attribute_id' => $select->id]);
    $chosen->translateOrNew('en_US')->label = 'Crimson Red';
    $chosen->save();

    $other = AttributeOption::create(['code' => 'cobalt', 'sort_order' => 2, 'attribute_id' => $select->id]);
    $other->translateOrNew('en_US')->label = 'Cobalt Blue';
    $other->save();

    $mapping->customAttributes()->attach($select, ['position' => 10]);

    $product = Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => ['sku' => 'PERF-SEL-1', 'perf_colour' => 'crimson']],
    ]);

    get(route('admin.catalog.products.edit', ['id' => $product->id]))
        ->assertOk()
        ->assertSee('Crimson Red')
        ->assertDontSee('Cobalt Blue');
});
