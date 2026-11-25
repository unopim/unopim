<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\DataGrid\DataGrid;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

beforeEach(function (): void {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();
});

/**
 * Request a datagrid the way its own JavaScript does.
 */
function requestDatagrid(string $route, array $query = []): TestResponse
{
    return get(route($route, $query), ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
}

/**
 * Request a datagrid and return the ORDER BY clause of the query that loaded its records.
 */
function datagridOrderBy(string $route, array $query, string $table): string
{
    $orderBy = '';

    DB::listen(function (QueryExecuted $executed) use (&$orderBy, $table): void {
        $sql = strtolower(str_replace(['"', '`'], '', $executed->sql));

        if (str_contains($sql, 'from '.DB::getTablePrefix().$table) && str_contains($sql, ' order by ')) {
            $orderBy = trim(substr($sql, strrpos($sql, ' order by ') + 10));
        }
    });

    requestDatagrid($route, $query)->assertOk();

    return $orderBy;
}

/**
 * Build a grid whose default sort column is not one of its columns and whose default page size is not a shared option.
 */
function gridWithOwnDefaults(): DataGrid
{
    $grid = new class extends DataGrid
    {
        protected $sortColumn = 'locales.id';

        protected $itemsPerPage = 25;

        public function prepareQueryBuilder()
        {
            return DB::table('locales')->select('id', 'code');
        }

        public function prepareColumns(): void
        {
            $this->addColumn([
                'index'      => 'code',
                'label'      => 'Code',
                'type'       => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable'   => true,
            ]);
        }
    };

    $grid->prepareColumns();

    return $grid;
}

describe('DataGrid sort input', function (): void {
    it('falls back to the default sort for a column the grid does not have', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'password', 'order' => 'asc']], 'locales');

        expect($orderBy)->toStartWith('status asc')
            ->and($orderBy)->not->toContain('password');
    });

    it('does not sort by a table column that is not a sortable grid column', function (): void {
        $orderBy = datagridOrderBy('admin.settings.users.index', ['sort' => ['column' => 'password', 'order' => 'asc']], 'admins');

        expect($orderBy)->toStartWith('user_id asc')
            ->and($orderBy)->not->toContain('password');
    });

    it('does not sort by a grid column that is not sortable', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'name', 'order' => 'asc']], 'locales');

        expect($orderBy)->toStartWith('status asc');
    });

    it('sorts by a sortable grid column', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'code', 'order' => 'asc']], 'locales');

        expect($orderBy)->toStartWith('code asc');
    });

    it('accepts the sort order in any letter case', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'code', 'order' => 'ASC']], 'locales');

        expect($orderBy)->toStartWith('code asc');
    });

    it('falls back to the default order for an order other than asc or desc', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'code', 'order' => 'sideways']], 'locales');

        expect($orderBy)->toStartWith('code desc');
    });

    it('falls back to the default order for an order given as an array', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => 'code', 'order' => ['asc']]], 'locales');

        expect($orderBy)->toStartWith('code desc');
    });

    it('falls back to the default sort for a column given as an array', function (): void {
        $orderBy = datagridOrderBy('admin.settings.locales.index', ['sort' => ['column' => ['code'], 'order' => 'asc']], 'locales');

        expect($orderBy)->toStartWith('status asc');
    });
});

describe('Product grid sort input', function (): void {
    it('sorts by an attribute that is not shown as a column', function (): void {
        $code = 'grid_sort_'.Str::lower(Str::random(6));

        Attribute::factory()->create(['code' => $code, 'type' => 'text']);

        Product::factory()->simple()->create();

        $orderBy = datagridOrderBy('admin.catalog.products.index', ['sort' => ['column' => $code, 'order' => 'asc']], 'products');

        expect($orderBy)->toContain($code)
            ->and($orderBy)->not->toStartWith(DB::getTablePrefix().'products.updated_at');
    });

    it('falls back to the default sort for a name that is neither a column nor an attribute', function (): void {
        Product::factory()->simple()->create();

        $orderBy = datagridOrderBy('admin.catalog.products.index', ['sort' => ['column' => 'not_an_attribute', 'order' => 'asc']], 'products');

        expect($orderBy)->toStartWith(DB::getTablePrefix().'products.updated_at asc')
            ->and($orderBy)->not->toContain('not_an_attribute');
    });
});

describe('DataGrid pagination input', function (): void {
    it('falls back to the default page size for a size outside the grid options', function (): void {
        $response = requestDatagrid('admin.settings.locales.index', ['pagination' => ['per_page' => 1000000]])->assertOk();

        expect($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('records'))->toHaveCount(min(10, Locale::count()));
    });

    it('keeps a page size from the grid options', function (): void {
        $response = requestDatagrid('admin.settings.locales.index', ['pagination' => ['per_page' => 50]])->assertOk();

        expect($response->json('meta.per_page'))->toBe(50)
            ->and($response->json('meta.per_page_options'))->toBe([10, 20, 30, 40, 50]);
    });

    it('caps the page size of the product grid', function (): void {
        Product::factory()->simple()->count(11)->create();

        $response = requestDatagrid('admin.catalog.products.index', ['pagination' => ['per_page' => 1000000]])->assertOk();

        expect($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('records'))->toHaveCount(10);
    });

    it('falls back to the first page for an invalid page number', function (mixed $page): void {
        $response = requestDatagrid('admin.settings.locales.index', ['pagination' => ['page' => $page]])->assertOk();

        expect($response->json('meta.current_page'))->toBe(1);
    })->with(['text' => 'abc', 'zero' => 0, 'negative' => -3]);
});

describe('DataGrid own defaults', function (): void {
    it('accepts the grid default sort column and page size even when they are not columns or shared options', function (): void {
        request()->merge(['sort' => ['column' => 'locales.id', 'order' => 'asc'], 'pagination' => ['per_page' => 25]]);

        $requestedParams = gridWithOwnDefaults()->validatedRequest();

        expect($requestedParams['sort'])->toBe(['column' => 'locales.id', 'order' => 'asc'])
            ->and($requestedParams['pagination'])->toBe(['per_page' => 25]);
    });

    it('still drops a page size that is neither a shared option nor the grid default', function (): void {
        request()->merge(['pagination' => ['per_page' => 26]]);

        expect(gridWithOwnDefaults()->validatedRequest()['pagination'])->toBe([]);
    });
});

describe('DataGrid productIds input', function (): void {
    it('ignores productIds on a grid that does not list products', function (): void {
        $response = requestDatagrid('admin.settings.locales.index', ['productIds' => [1]])->assertOk();

        expect($response->json('meta.total'))->toBe(Locale::count());
    });

    it('still narrows the product grid to the requested product ids', function (): void {
        $products = Product::factory()->simple()->count(2)->create();

        $response = requestDatagrid('admin.catalog.products.index', ['productIds' => [$products->first()->id]])->assertOk();

        expect($response->json('meta.total'))->toBe(1)
            ->and($response->json('records.0.product_id'))->toBe($products->first()->id);
    });
});
