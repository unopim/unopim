<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Category\Models\Category;

uses(DatabaseTransactions::class);

it('can create a category via factory', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->id)->toBeInt();
    expect($category->code)->toBeString();
    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

it('casts additional_data to array', function () {
    $category = Category::factory()->create();

    expect($category->additional_data)->toBeArray();
    expect($category->additional_data)->toHaveKey('locale_specific');
});

it('requires a unique code', function () {
    $category = Category::factory()->create(['code' => 'unique_test_code']);

    $this->assertDatabaseHas('categories', ['code' => 'unique_test_code']);

    expect(fn () => Category::factory()->create(['code' => 'unique_test_code']))
        ->toThrow(QueryException::class);
});

it('has a parent_category relationship linking child to parent', function () {
    $root = Category::whereIsRoot()->first();

    $child = Category::factory()->create([
        'parent_id' => $root->id,
    ]);

    expect($child->parent_category)->toBeInstanceOf(Category::class);
    expect($child->parent_category->id)->toBe($root->id);
});

it('returns locale-specific name from additional_data via name accessor', function () {
    $localeCode = core()->getRequestedLocaleCode();
    $expectedName = 'Test Category Name';

    $category = Category::factory()->create([
        'code'            => 'name_accessor_test',
        'additional_data' => [
            'locale_specific' => [
                $localeCode => [
                    'name' => $expectedName,
                ],
            ],
        ],
    ]);

    expect($category->name)->toBe($expectedName);
});

it('falls back to [code] when locale name is missing from additional_data', function () {
    $category = Category::factory()->create([
        'code'            => 'fallback_code_test',
        'additional_data' => [
            'locale_specific' => [],
        ],
    ]);

    expect($category->name)->toBe('[fallback_code_test]');
});

it('falls back to [code] when additional_data has no locale_specific key', function () {
    $category = Category::factory()->create([
        'code'            => 'no_locale_key',
        'additional_data' => [],
    ]);

    expect($category->name)->toBe('[no_locale_key]');
});

it('can have children via parent_id', function () {
    $root = Category::whereIsRoot()->first();

    $child1 = Category::factory()->create(['parent_id' => $root->id]);
    $child2 = Category::factory()->create(['parent_id' => $root->id]);

    $children = Category::where('parent_id', $root->id)->get();

    expect($children->pluck('id')->toArray())
        ->toContain($child1->id)
        ->toContain($child2->id);
});

it('has null parent_id for root categories', function () {
    $root = Category::whereIsRoot()->first();

    expect($root->parent_id)->toBeNull();
    expect($root->parent_category)->toBeNull();
});

it('appends name attribute in toArray output', function () {
    $category = Category::factory()->create();

    $array = $category->toArray();

    expect($array)->toHaveKey('name');
});

it('excludes _lft, _rgt, and id from audit history', function () {
    $category = new Category;

    $reflection = new ReflectionProperty($category, 'auditExclude');
    $auditExclude = $reflection->getValue($category);

    expect($auditExclude)->toContain('_lft');
    expect($auditExclude)->toContain('_rgt');
    expect($auditExclude)->toContain('id');
});

it('has code and parent_id as fillable attributes', function () {
    $category = new Category;

    expect($category->getFillable())->toBe(['code', 'parent_id']);
});
