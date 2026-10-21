<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Validator\Catalog\CategoryRequestValidator;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));

    foreach (['shared/root/front.png', 'shared/root/other.png'] as $path) {
        Storage::put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }

    $this->locale = core()->getRequestedLocaleCode();
});

function categoryWithData(array $additionalData): Category
{
    $category = Category::factory()->create();

    $category->additional_data = array_replace_recursive($category->additional_data ?? [], $additionalData);
    $category->saveQuietly();

    return $category;
}

function validateCategoryData(Category $category, array $additionalData): void
{
    app(CategoryRequestValidator::class)->validate([
        'code'            => $category->code,
        'additional_data' => array_replace_recursive($category->additional_data ?? [], $additionalData),
    ], $category->id);
}

it('accepts a stored image path outside the category directory when it is resubmitted unchanged', function () {
    $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

    $category = categoryWithData(['common' => [$field->code => 'shared/root/front.png']]);

    validateCategoryData($category, ['common' => [$field->code => 'shared/root/front.png']]);

    expect(true)->toBeTrue();
});

it('accepts a stored locale specific path for the same locale', function () {
    $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image', 'value_per_locale' => true]);

    $category = categoryWithData(['locale_specific' => [$this->locale => [$field->code => 'shared/root/front.png']]]);

    validateCategoryData($category, ['locale_specific' => [$this->locale => [$field->code => 'shared/root/front.png']]]);

    expect(true)->toBeTrue();
});

it('rejects a new path outside the category directory', function () {
    $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

    $category = categoryWithData(['common' => [$field->code => 'shared/root/front.png']]);

    validateCategoryData($category, ['common' => [$field->code => 'shared/root/other.png']]);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a stored path moved to a different field', function () {
    $stored = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);
    $target = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

    $category = categoryWithData(['common' => [$stored->code => 'shared/root/front.png']]);

    validateCategoryData($category, ['common' => [$target->code => 'shared/root/front.png']]);
})->throws(ValidationException::class, 'does not belong to this record');

it('rejects a path stored on another category', function () {
    $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

    categoryWithData(['common' => [$field->code => 'shared/root/front.png']]);

    $category = categoryWithData([]);

    validateCategoryData($category, ['common' => [$field->code => 'shared/root/front.png']]);
})->throws(ValidationException::class, 'does not belong to this record');
