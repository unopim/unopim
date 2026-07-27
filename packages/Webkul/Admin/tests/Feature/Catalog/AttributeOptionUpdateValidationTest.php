<?php

use Illuminate\Http\UploadedFile;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

/*
 * The option update endpoint must enforce the same swatch upload validation as
 * create: an image-swatch attribute must reject a non-image file, closing the
 * unrestricted-upload gap where update accepted any file.
 */
it('rejects a non-image swatch upload on option update', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);
    $option = AttributeOption::create(['code' => 'red', 'sort_order' => 1, 'attribute_id' => $attribute->id]);

    $this->put(
        route('admin.catalog.attributes.options.update', ['attribute_id' => $attribute->id, 'id' => $option->id]),
        [
            'locales'      => ['en_US' => ['label' => 'Red']],
            'swatch_value' => UploadedFile::fake()->create('evil.php', 10),
        ],
        ['Accept' => 'application/json']
    )->assertStatus(422);
});

it('accepts a label-only option update', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);
    $option = AttributeOption::create(['code' => 'blue', 'sort_order' => 1, 'attribute_id' => $attribute->id]);

    $this->put(
        route('admin.catalog.attributes.options.update', ['attribute_id' => $attribute->id, 'id' => $option->id]),
        ['locales' => ['en_US' => ['label' => 'Blue']]]
    )->assertOk();
});
