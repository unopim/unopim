<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->categoryRepository = app(CategoryRepository::class);
});

it('creates a category and persists it to the database', function () {
    $localeCode = core()->getRequestedLocaleCode();

    $data = [
        'code'            => 'repo_create_test',
        'parent_id'       => Category::whereIsRoot()->first()->id,
        'additional_data' => [
            'locale_specific' => [
                $localeCode => [
                    'name' => 'Repository Created Category',
                ],
            ],
        ],
    ];

    $category = $this->categoryRepository->create($data, withoutFormattingValues: true);

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->code)->toBe('repo_create_test');
    $this->assertDatabaseHas('categories', ['code' => 'repo_create_test']);
});

it('stores additional_data correctly when creating a category', function () {
    $localeCode = core()->getRequestedLocaleCode();
    $expectedName = 'Additional Data Test';

    $data = [
        'code'            => 'additional_data_test',
        'parent_id'       => Category::whereIsRoot()->first()->id,
        'additional_data' => [
            'locale_specific' => [
                $localeCode => [
                    'name' => $expectedName,
                ],
            ],
        ],
    ];

    $category = $this->categoryRepository->create($data, withoutFormattingValues: true);

    expect($category->additional_data)->toBeArray();
    expect($category->additional_data['locale_specific'][$localeCode]['name'])->toBe($expectedName);
});

it('creates a category without additional_data', function () {
    $data = [
        'code'      => 'no_additional_data',
        'parent_id' => Category::whereIsRoot()->first()->id,
    ];

    $category = $this->categoryRepository->create($data);

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->code)->toBe('no_additional_data');
    expect($category->additional_data)->toBeNull();
    $this->assertDatabaseHas('categories', ['code' => 'no_additional_data']);
});

it('updates a category data', function () {
    $localeCode = core()->getRequestedLocaleCode();

    $category = Category::factory()->create([
        'code' => 'update_test_original',
    ]);

    $updatedData = [
        'code'            => 'update_test_original',
        'additional_data' => [
            'locale_specific' => [
                $localeCode => [
                    'name' => 'Updated Name',
                ],
            ],
        ],
    ];

    $updated = $this->categoryRepository->update($updatedData, $category->id, withoutFormattingValues: true);

    expect($updated->additional_data['locale_specific'][$localeCode]['name'])->toBe('Updated Name');
});

it('updates additional_data with common values', function () {
    $localeCode = core()->getRequestedLocaleCode();

    $category = Category::factory()->create();

    $updatedData = [
        'code'            => $category->code,
        'additional_data' => [
            'common' => [
                'description' => 'A common description',
            ],
            'locale_specific' => [
                $localeCode => [
                    'name' => 'With Common Values',
                ],
            ],
        ],
    ];

    $updated = $this->categoryRepository->update($updatedData, $category->id, withoutFormattingValues: true);

    expect($updated->additional_data)->toHaveKey('common');
    expect($updated->additional_data['common']['description'])->toBe('A common description');
});

it('returns only root categories from getRootCategories', function () {
    // Ensure at least one root category exists (seeded)
    $roots = $this->categoryRepository->getRootCategories();

    expect($roots)->not->toBeEmpty();

    $roots->each(function ($category) {
        expect($category->parent_id)->toBeNull();
    });
});

it('does not include child categories in getRootCategories', function () {
    $root = Category::whereIsRoot()->first();
    $child = Category::factory()->create(['parent_id' => $root->id]);

    $roots = $this->categoryRepository->getRootCategories();

    expect($roots->pluck('id')->toArray())->not->toContain($child->id);
});

it('returns category tree excluding a specific category', function () {
    $root = Category::whereIsRoot()->first();
    $child = Category::factory()->create(['parent_id' => $root->id]);

    $tree = $this->categoryRepository->getCategoryTree($child->id);

    $flatIds = $tree->pluck('id')->toArray();

    expect($flatIds)->not->toContain($child->id);
});

it('returns full category tree when no id is provided', function () {
    $tree = $this->categoryRepository->getCategoryTree();

    expect($tree)->not->toBeEmpty();
});

it('returns child categories for a given parent', function () {
    $root = Category::whereIsRoot()->first();
    $child1 = Category::factory()->create(['parent_id' => $root->id]);
    $child2 = Category::factory()->create(['parent_id' => $root->id]);

    $children = $this->categoryRepository->getChildCategories($root->id);

    $childIds = $children->pluck('id')->toArray();

    expect($childIds)->toContain($child1->id);
    expect($childIds)->toContain($child2->id);
});

it('excludes a specific category from child results', function () {
    $root = Category::whereIsRoot()->first();
    $child1 = Category::factory()->create(['parent_id' => $root->id]);
    $child2 = Category::factory()->create(['parent_id' => $root->id]);

    $children = $this->categoryRepository->getChildCategories($root->id, $child1->id);

    $childIds = $children->pluck('id')->toArray();

    expect($childIds)->not->toContain($child1->id);
    expect($childIds)->toContain($child2->id);
});

it('returns expected structure from getPartial', function () {
    $category = Category::factory()->create();

    $partials = $this->categoryRepository->getPartial(null);

    expect($partials)->toBeArray();
    expect(count($partials))->toBeGreaterThanOrEqual(1);

    $first = reset($partials);

    expect($first)->toHaveKeys(['id', 'name', 'slug']);
});

it('defines the correct model class', function () {
    expect($this->categoryRepository->model())->toBe(Webkul\Category\Contracts\Category::class);
});
