<?php

use Webkul\Admin\Http\Controllers\Catalog\CategoryController;
use Webkul\Category\Models\CategoryField;

function sanitizeCategoryData(array $data): array
{
    $controller = app(CategoryController::class);

    $method = new ReflectionMethod($controller, 'sanitizeAdditionalData');
    $method->setAccessible(true);

    return $method->invoke($controller, $data);
}

it('sanitizes wysiwyg category-field values in additional_data', function () {
    $field = CategoryField::factory()->create([
        'type'             => 'textarea',
        'enable_wysiwyg'   => 1,
        'status'           => 1,
        'value_per_locale' => 0,
    ]);

    $out = sanitizeCategoryData([
        'additional_data' => [
            'common' => [
                $field->code => '<p>Hi</p><script>bad()</script><img src=x onerror=alert(1)>',
            ],
        ],
    ]);

    expect($out['additional_data']['common'][$field->code])
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->toContain('Hi');
});
