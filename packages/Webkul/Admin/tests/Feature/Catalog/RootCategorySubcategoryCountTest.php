<?php

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\Installer\Database\Seeders\Category\CategoryTableSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCategorySeeder;

function seedInstallerRoot(): object
{
    resolve(CategoryTableSeeder::class)->run();

    return DB::table('categories')->where('code', 'root')->first();
}

function browseTreeHtml(): string
{
    $response = test()->get(route('admin.catalog.categories.index', ['view' => 'tree']));

    $response->assertOk();

    return $response->content();
}

function renderedSubcategoryCounts(string $html): array
{
    preg_match_all('/([\d,]+) subcategories/', $html, $matches);

    return array_map(fn (string $count) => (int) str_replace(',', '', $count), $matches[1]);
}

function realDescendantCount(int $rootId): int
{
    return Category::whereNot('id', $rootId)->count();
}

it('gives the installer root the nested set bounds of a childless node', function () {
    $root = seedInstallerRoot();

    expect(DB::table('categories')->count())->toBe(1)
        ->and((int) $root->_lft)->toBe(1)
        ->and((int) $root->_rgt)->toBe(2)
        ->and(Category::where('parent_id', $root->id)->count())->toBe(0)
        ->and((int) ((($root->_rgt - $root->_lft) - 1) / 2))->toBe(0);
});

it('reports no subcategories in the browse panel for a root that has none', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    expect(renderedSubcategoryCounts(browseTreeHtml()))->toBe([0])
        ->and(realDescendantCount((int) $root->id))->toBe(0);
});

it('derives the panel count from the nested set bounds, not from row or locale counts', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    $activeLocales = DB::table('locales')->where('status', 1)->count();

    expect(DB::table('categories')->count())->toBe(1)
        ->and(renderedSubcategoryCounts(browseTreeHtml()))->toBe([0]);

    DB::table('categories')->where('id', $root->id)->update(['_rgt' => 24]);

    expect(DB::table('categories')->count())->toBe(1)
        ->and(DB::table('locales')->where('status', 1)->count())->toBe($activeLocales)
        ->and(renderedSubcategoryCounts(browseTreeHtml()))->toBe([11]);
});

it('counts exactly the real children once they are added', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    Category::create(['code' => 'probe_one', 'parent_id' => $root->id]);
    Category::create(['code' => 'probe_two', 'parent_id' => $root->id]);

    expect(realDescendantCount((int) $root->id))->toBe(2)
        ->and(renderedSubcategoryCounts(browseTreeHtml()))->toBe([2]);
});

it('agrees with the tree endpoint', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    $children = $this->getJson(route('admin.catalog.categories.children.tree', [
        'id'   => $root->id,
        'page' => 1,
    ]));

    $children->assertOk();

    expect($children->json('total'))->toBe(0)
        ->and($children->json('data'))->toBe([])
        ->and(renderedSubcategoryCounts(browseTreeHtml()))->toBe([0]);
});

it('matches the real descendant count for the demo seeded tree', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    resolve(DemoCategorySeeder::class)->run();

    $expected = count((require base_path('packages/Webkul/Installer/src/Database/Data/Demo/categories.php'))['tree']);

    expect(realDescendantCount((int) $root->id))->toBe($expected)
        ->and(renderedSubcategoryCounts(browseTreeHtml()))->toBe([$expected]);
});

it('repairs a legacy tree whose bounds were left with gaps', function () {
    $this->loginAsAdmin();

    $root = seedInstallerRoot();

    resolve(DemoCategorySeeder::class)->run();

    DB::table('categories')->where('id', $root->id)->update(['_rgt' => 40]);
    DB::table('categories')->where('code', 'apparel')->update(['_lft' => 14, '_rgt' => 19]);

    expect(renderedSubcategoryCounts(browseTreeHtml()))->toBe([19]);

    Category::fixTree();

    expect(renderedSubcategoryCounts(browseTreeHtml()))->toBe([13])
        ->and((int) Category::max('_rgt'))->toBe(Category::count() * 2);
});
