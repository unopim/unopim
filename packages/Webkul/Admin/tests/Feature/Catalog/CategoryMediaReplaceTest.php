<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Models\Locale;

function submitCategoryForm(Category $category, array $parameters, array $files = [], array $query = [])
{
    $uri = route('admin.catalog.categories.update', ['id' => $category->id] + $query);

    $locale = $query['locale'] ?? core()->getRequestedLocaleCode();

    $name = $category->additional_data['locale_specific'][$locale]['name'] ?? $category->code;

    $parameters = array_replace_recursive([
        'code'            => $category->code,
        'parent_id'       => $category->parent_id,
        'additional_data' => ['locale_specific' => [$locale => ['name' => $name]]],
    ], $parameters);

    return submitMultipartForm($uri, 'PUT', $parameters, $files);
}

function categoryFieldValue(Category $category, string $code, ?string $locale = null)
{
    $data = $category->additional_data ?? [];

    return $locale
        ? ($data['locale_specific'][$locale][$code] ?? null)
        : ($data['common'][$code] ?? null);
}

describe('category creation', function () {
    it('stores an image field when the category is created', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

        Storage::fake();

        $data = Category::factory()->definition();

        submitMultipartForm(route('admin.catalog.categories.store'), 'POST', $data, [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('category.jpg')]]],
        ]);

        $category = Category::where('code', $data['code'])->first();

        expect($category)->not->toBeNull()
            ->and(categoryFieldValue($category, $field->code))->toContain('category.jpg')
            ->and(Storage::exists(categoryFieldValue($category, $field->code)))->toBeTrue();
    });

    it('stores a file field when the category is created', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'file']);

        Storage::fake();

        $data = Category::factory()->definition();

        submitMultipartForm(route('admin.catalog.categories.store'), 'POST', $data, [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->create('category.pdf', 100)]]],
        ]);

        $category = Category::where('code', $data['code'])->first();

        expect($category)->not->toBeNull()
            ->and(categoryFieldValue($category, $field->code))->toContain('category.pdf')
            ->and(Storage::exists(categoryFieldValue($category, $field->code)))->toBeTrue();
    });
});

describe('category media replacement', function () {
    it('keeps the replacement when an existing category image is re-uploaded', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toContain('first.jpg');

        submitCategoryForm($category, [
            'additional_data' => ['common' => [$field->code => '']],
        ], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('second.jpg')]]],
        ]);

        $category->refresh();

        $second = categoryFieldValue($category, $field->code);

        expect($second)->toContain('second.jpg')
            ->and(Storage::exists($second))->toBeTrue();
    });

    it('keeps the replacement when an existing category file is re-uploaded', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'file']);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->create('first.pdf', 100)]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toContain('first.pdf');

        submitCategoryForm($category, [
            'additional_data' => ['common' => [$field->code => '']],
        ], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->create('second.pdf', 100)]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toContain('second.pdf');
    });

    it('clears the category image when it is removed without a replacement', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toContain('first.jpg');

        submitCategoryForm($category, [
            'additional_data' => ['common' => [$field->code => '']],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toBeNull();
    });

    it('preserves the category image when the form is saved without touching it', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $category->refresh();

        $stored = categoryFieldValue($category, $field->code);

        submitCategoryForm($category, [
            'additional_data' => ['common' => [$field->code => $stored]],
        ], [
            'additional_data' => ['common' => [$field->code => [0 => null]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toBe($stored);
    });
});

describe('category media across locales', function () {
    it('replaces a locale specific image without touching the other locale', function () {
        $this->loginAsAdmin();

        Locale::where('code', 'fr_FR')->update(['status' => 1]);

        $defaultLocale = core()->getRequestedLocaleCode();

        $field = CategoryField::factory()->create([
            'status'           => 1,
            'type'             => 'image',
            'value_per_locale' => 1,
        ]);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['locale_specific' => [$defaultLocale => [$field->code => [UploadedFile::fake()->image('en-first.jpg')]]]],
        ], ['locale' => $defaultLocale]);

        submitCategoryForm($category, [], [
            'additional_data' => ['locale_specific' => ['fr_FR' => [$field->code => [UploadedFile::fake()->image('fr-first.jpg')]]]],
        ], ['locale' => 'fr_FR']);

        $category->refresh();

        $englishStored = categoryFieldValue($category, $field->code, $defaultLocale);

        expect($englishStored)->toContain('en-first.jpg')
            ->and(categoryFieldValue($category, $field->code, 'fr_FR'))->toContain('fr-first.jpg');

        submitCategoryForm($category, [
            'additional_data' => ['locale_specific' => ['fr_FR' => [$field->code => '']]],
        ], [
            'additional_data' => ['locale_specific' => ['fr_FR' => [$field->code => [UploadedFile::fake()->image('fr-second.jpg')]]]],
        ], ['locale' => 'fr_FR']);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code, 'fr_FR'))->toContain('fr-second.jpg')
            ->and(categoryFieldValue($category, $field->code, $defaultLocale))->toBe($englishStored);
    });
});

describe('category media validation', function () {
    it('rejects a replacement with a disallowed extension and keeps the stored image', function () {
        $this->loginAsAdmin();

        $field = CategoryField::factory()->create(['status' => 1, 'type' => 'image']);

        $category = Category::factory()->create();

        Storage::fake();

        submitCategoryForm($category, [], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->image('first.jpg')]]],
        ]);

        $category->refresh();

        $stored = categoryFieldValue($category, $field->code);

        submitCategoryForm($category, [
            'additional_data' => ['common' => [$field->code => '']],
        ], [
            'additional_data' => ['common' => [$field->code => [UploadedFile::fake()->create('payload.php', 10)]]],
        ]);

        $category->refresh();

        expect(categoryFieldValue($category, $field->code))->toBe($stored);
    });
});
