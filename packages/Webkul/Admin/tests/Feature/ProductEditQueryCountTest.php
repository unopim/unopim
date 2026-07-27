<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

/*
 * Query-count regression guard for the product edit page (F1/F2/F6 of the
 * "Product Detail Page — Performance Analysis"). Rendering the form must stay a
 * small, near-constant number of queries regardless of how many attributes the
 * family carries — never one lazy translation load per attribute, nor a full
 * option-set load per select attribute.
 */
function makeProductWithAttributeCount(int $textAttributes): Product
{
    $family = AttributeFamily::factory()->create();

    $group = AttributeGroup::factory()->create();

    $family->familyGroups()->attach($group);

    $group->translateOrNew('en_US')->name = 'Performance Group';
    $group->save();

    $mapping = $family->attributeFamilyGroupMappings()->first();

    foreach (Attribute::whereIn('code', ['sku', 'status'])->get() as $position => $attribute) {
        $mapping->customAttributes()->attach($attribute, ['position' => $position + 1]);
    }

    $commonValues = ['sku' => 'PERF-'.uniqid()];

    for ($i = 1; $i <= $textAttributes; $i++) {
        $text = Attribute::factory()->create(['type' => 'text']);
        $text->translateOrNew('en_US')->name = "Perf Text {$i}";
        $text->save();
        $mapping->customAttributes()->attach($text, ['position' => $i + 100]);

        $select = Attribute::factory()->create(['type' => 'select', 'code' => 'perf_select_'.$i.'_'.uniqid()]);
        $select->translateOrNew('en_US')->name = "Perf Select {$i}";
        $select->save();

        $option = AttributeOption::create(['code' => 'opt_'.$i.'_'.uniqid(), 'sort_order' => 1, 'attribute_id' => $select->id]);
        $option->translateOrNew('en_US')->label = "Option {$i}";
        $option->save();

        $mapping->customAttributes()->attach($select, ['position' => $i + 500]);

        $commonValues[$select->code] = $option->code;
    }

    return Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'values'              => ['common' => $commonValues],
    ]);
}

function countEditQueries(Product $product): int
{
    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    get(route('admin.catalog.products.edit', ['id' => $product->id]))->assertOk();

    return $queries;
}

it('renders the product edit page without scaling queries per attribute (F1/F2/F6)', function () {
    $this->loginAsAdmin();

    $small = makeProductWithAttributeCount(3);
    $large = makeProductWithAttributeCount(18);

    $delta = countEditQueries($large) - countEditQueries($small);

    expect($delta)->toBeLessThanOrEqual(10);
});
