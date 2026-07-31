<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\get;

/*
 * Regression guard for the attributes/edit `v-show` fix.
 *
 * The "Textarea Switcher" control-group used `v-show="{{ $attribute->type == 'textarea' }}"`.
 * When the expression was false PHP echoed an empty string, producing a broken
 * `v-show=""` in the compiled Vue template. The fix casts it to an explicit
 * boolean literal: `v-show="{{ ... ? 'true' : 'false' }}"`, so the rendered
 * attribute is always a valid `v-show="true"` or `v-show="false"`, never empty.
 */

it('renders a valid v-show="false" for a non-textarea attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'text']);

    $response = get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]));

    $response->assertOk();
    $response->assertSee('v-show="false"', false);
    $response->assertDontSee('v-show=""', false);
});

it('renders a valid v-show="true" for a textarea attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'textarea']);

    $response = get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]));

    $response->assertOk();
    $response->assertSee('v-show="true"', false);
    $response->assertDontSee('v-show=""', false);
});
