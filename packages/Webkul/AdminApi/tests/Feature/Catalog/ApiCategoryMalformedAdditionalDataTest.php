<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\AdminApi\Http\Middleware\LocaleMiddleware;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Models\Locale;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

dataset('malformedAdditionalData', [
    'common is a string'                => [['common' => 'not-an-object']],
    'locale_specific is a string'       => [['locale_specific' => 'not-an-object']],
    'locale_specific entry is a string' => [['locale_specific' => ['en_US' => 'not-an-object']]],
    'locale_specific entry is null'     => [['locale_specific' => ['en_US' => null]]],
]);

it('returns 422 instead of a server error for malformed additional_data on create', function (array $additionalData): void {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), [
            'code'            => 'edge_category_'.uniqid(),
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');

it('returns 422 instead of a server error for malformed additional_data on update', function (array $additionalData): void {
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), [
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');

it('returns 422 instead of a server error for malformed additional_data on partial update', function (array $additionalData): void {
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), [
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');

it('still accepts a valid additional_data payload on create, update and partial update', function (): void {
    $locale = Locale::where('status', 1)->first();

    $payload = ['additional_data' => ['locale_specific' => [$locale->code => ['name' => 'Valid Category']]]];

    $code = 'valid_category_'.uniqid();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), ['code' => $code] + $payload)
        ->assertSuccessful();

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.categories.update', ['code' => $code]), $payload)
        ->assertSuccessful();

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $code]), $payload)
        ->assertSuccessful();
});

it('ignores a non-array labels or locale_specific value when reading locales', function (): void {
    $middleware = app(LocaleMiddleware::class);

    expect($middleware->checkKeyExists(['labels' => 'not-an-array'], 'labels'))->toBe([])
        ->and($middleware->checkKeyExists(['labels' => ['en_US' => 'A']], 'labels'))->toBe(['en_US'])
        ->and($middleware->checkKeyExists(['x' => ['labels' => 'text']], 'labels'))->toBe([]);
});

it('does not return a server error for an array leaf value', function (): void {
    $locale = Locale::where('status', 1)->first();

    $response = $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), [
            'code'            => 'leaf_category_'.uniqid(),
            'additional_data' => ['locale_specific' => [$locale->code => ['name' => ['nested' => 'x']]]],
        ]);

    expect($response->status())->toBeLessThan(500);
});

dataset('uploadedFileInAdditionalData', [
    'common leaf' => [fn (string $commonCode, string $locale): array => [
        'common'          => [$commonCode => UploadedFile::fake()->create('shell.php', 1, 'text/html')],
        'locale_specific' => [$locale => ['name' => 'Uploaded']],
    ]],
    'locale specific leaf' => [fn (string $commonCode, string $locale): array => [
        'locale_specific' => [$locale => ['name' => UploadedFile::fake()->create('page.html', 1, 'text/html')]],
    ]],
    'nested array leaf' => [fn (string $commonCode, string $locale): array => [
        'common'          => [$commonCode => [UploadedFile::fake()->image('photo.jpg')]],
        'locale_specific' => [$locale => ['name' => 'Uploaded']],
    ]],
]);

/**
 * Build an additional_data payload that places an uploaded file on active text fields.
 *
 * Text fields carry no type rule in the category field validator, so they are the leaves the
 * repository would otherwise hand to the file storer.
 */
function additionalDataWithUploadedFile(Closure $payload): array
{
    $commonField = CategoryField::factory()->create(['type' => 'text', 'status' => 1, 'value_per_locale' => 0, 'validation' => null]);

    return $payload($commonField->code, Locale::where('status', 1)->first()->code);
}

it('rejects an uploaded file inside additional_data on create without storing it', function (Closure $payload): void {
    Storage::fake();

    $code = 'upload_category_'.uniqid();

    $this->withHeaders($this->headers)
        ->post(route('admin.api.categories.store'), [
            'code'            => $code,
            'additional_data' => additionalDataWithUploadedFile($payload),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['additional_data' => trans('core::validation.uploaded-file-not-allowed', ['attribute' => 'additional data'])]);

    expect(Storage::allFiles())->toBe([])
        ->and(Category::where('code', $code)->exists())->toBeFalse();
})->with('uploadedFileInAdditionalData');

it('rejects an uploaded file inside additional_data on update without storing it', function (Closure $payload): void {
    Storage::fake();

    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->put(route('admin.api.categories.update', ['code' => $category->code]), [
            'additional_data' => additionalDataWithUploadedFile($payload),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['additional_data' => trans('core::validation.uploaded-file-not-allowed', ['attribute' => 'additional data'])]);

    expect(Storage::allFiles())->toBe([])
        ->and($category->fresh()->additional_data)->toEqual($category->additional_data);
})->with('uploadedFileInAdditionalData');

it('rejects an uploaded file inside additional_data on partial update without storing it', function (Closure $payload): void {
    Storage::fake();

    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->patch(route('admin.api.categories.patch', ['code' => $category->code]), [
            'additional_data' => additionalDataWithUploadedFile($payload),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['additional_data' => trans('core::validation.uploaded-file-not-allowed', ['attribute' => 'additional data'])]);

    expect(Storage::allFiles())->toBe([])
        ->and($category->fresh()->additional_data)->toEqual($category->additional_data);
})->with('uploadedFileInAdditionalData');
