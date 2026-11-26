<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Locale;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Build an additional_data payload naming the category in the first active locale.
 */
function apiCatParentNames(string $name): array
{
    $locale = Locale::where('status', 1)->first();

    return ['locale_specific' => [$locale->code => ['name' => $name]]];
}

/**
 * Create a parent category and a child under it through the API, returning both codes.
 */
function apiCatParentCreateChild(object $test, string $suffix): array
{
    $parentCode = 'par_'.$suffix;
    $childCode = 'child_'.$suffix;

    $test->withHeaders($test->headers)
        ->postJson(route('admin.api.categories.store'), ['code' => $parentCode, 'additional_data' => apiCatParentNames('Parent')])
        ->assertStatus(201);

    $test->withHeaders($test->headers)
        ->postJson(route('admin.api.categories.store'), ['code' => $childCode, 'parent' => $parentCode, 'additional_data' => apiCatParentNames('Child')])
        ->assertStatus(201);

    return [$parentCode, $childCode];
}

/**
 * Resolve the parent code of the category with the given code.
 */
function apiCatParentCodeOf(string $code): ?string
{
    return Category::where('code', $code)->first()->parent?->code;
}

it('keeps the parent when a PUT does not send a parent', function (): void {
    [$parentCode, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['additional_data' => apiCatParentNames('Renamed')])
        ->assertOk();

    expect(apiCatParentCodeOf($childCode))->toBe($parentCode);
});

it('keeps the parent when a PATCH does not send a parent', function (): void {
    [$parentCode, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.categories.patch', ['code' => $childCode]), ['additional_data' => apiCatParentNames('Renamed')])
        ->assertOk();

    expect(apiCatParentCodeOf($childCode))->toBe($parentCode);
});

it('detaches a category to the root when PATCH sends parent null', function (): void {
    [, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.categories.patch', ['code' => $childCode]), ['parent' => null])
        ->assertOk();

    expect(apiCatParentCodeOf($childCode))->toBeNull();
});

it('detaches a category to the root when PUT sends parent null', function (): void {
    [, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['parent' => null])
        ->assertOk();

    expect(apiCatParentCodeOf($childCode))->toBeNull();
});

it('moves a category under another parent when a parent code is sent', function (): void {
    [, $childCode] = apiCatParentCreateChild($this, uniqid());
    [$otherParent] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['parent' => $otherParent])
        ->assertOk();

    expect(apiCatParentCodeOf($childCode))->toBe($otherParent);
});

it('rejects an unknown parent code on PUT and keeps the current parent', function (): void {
    [$parentCode, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['parent' => 'missing_'.uniqid()])
        ->assertUnprocessable();

    expect(apiCatParentCodeOf($childCode))->toBe($parentCode);
});

it('rejects an unknown parent code on PATCH and keeps the current parent', function (): void {
    [$parentCode, $childCode] = apiCatParentCreateChild($this, uniqid());

    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.categories.patch', ['code' => $childCode]), ['parent' => 'missing_'.uniqid()])
        ->assertUnprocessable();

    expect(apiCatParentCodeOf($childCode))->toBe($parentCode);
});
