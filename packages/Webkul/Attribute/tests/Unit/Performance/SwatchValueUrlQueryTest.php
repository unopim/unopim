<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

/**
 * Guards against F3 from the product detail performance analysis.
 *
 * swatch_value_url is an appended accessor, so it fires on every toArray()/JSON
 * serialization of an option (product edit page and the async options endpoint).
 * When it evaluates $this->attribute before checking $this->swatch_value, every
 * swatch-less option lazy-loads its parent attribute — one wasted query per option.
 */
it('does not query the parent attribute for a swatch-less option (F3)', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $option = AttributeOption::create([
        'code'         => 'plain_option',
        'sort_order'   => 1,
        'attribute_id' => $attribute->id,
        'swatch_value' => null,
    ]);

    $fresh = AttributeOption::find($option->id);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $fresh->swatch_value_url;

    expect(DB::getQueryLog())->toHaveCount(0);
});
