<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Locale;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

function categoryNames(string $name): array
{
    $locale = Locale::where('status', 1)->first();

    return ['locale_specific' => [$locale->code => ['name' => $name]]];
}

function createChildCategory(object $test, string $suffix): array
{
    $parentCode = 'par_'.$suffix;
    $childCode = 'child_'.$suffix;

    $test->withHeaders($test->headers)
        ->postJson(route('admin.api.categories.store'), ['code' => $parentCode, 'additional_data' => categoryNames('Parent')])
        ->assertStatus(201);

    $test->withHeaders($test->headers)
        ->postJson(route('admin.api.categories.store'), ['code' => $childCode, 'parent' => $parentCode, 'additional_data' => categoryNames('Child')])
        ->assertStatus(201);

    return [$parentCode, $childCode];
}

function parentCodeOf(string $code): ?string
{
    return Category::where('code', $code)->first()->parent?->code;
}

it('keeps the parent when a PUT does not send a parent', function (): void {
    [$parentCode, $childCode] = createChildCategory($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['additional_data' => categoryNames('Renamed')])
        ->assertOk();

    expect(parentCodeOf($childCode))->toBe($parentCode);
});

it('keeps the parent when a PATCH does not send a parent', function (): void {
    [$parentCode, $childCode] = createChildCategory($this, uniqid());

    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.categories.patch', ['code' => $childCode]), ['additional_data' => categoryNames('Renamed')])
        ->assertOk();

    expect(parentCodeOf($childCode))->toBe($parentCode);
});

it('detaches a category to the root when PATCH sends parent null', function (): void {
    [, $childCode] = createChildCategory($this, uniqid());

    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.categories.patch', ['code' => $childCode]), ['parent' => null])
        ->assertOk();

    expect(parentCodeOf($childCode))->toBeNull();
});

it('detaches a category to the root when PUT sends parent null', function (): void {
    [, $childCode] = createChildCategory($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['parent' => null])
        ->assertOk();

    expect(parentCodeOf($childCode))->toBeNull();
});

it('moves a category under another parent when a parent code is sent', function (): void {
    [, $childCode] = createChildCategory($this, uniqid());
    [$otherParent] = createChildCategory($this, uniqid());

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.categories.update', ['code' => $childCode]), ['parent' => $otherParent])
        ->assertOk();

    expect(parentCodeOf($childCode))->toBe($otherParent);
});
