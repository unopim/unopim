<?php

use Illuminate\Testing\TestResponse;
use Webkul\DataGrid\DataGrid;
use Webkul\User\Tests\Concerns\UserAssertions;

use function Pest\Laravel\getJson;

uses(UserAssertions::class);

beforeEach(function (): void {
    $this->loginAsAdmin();
});

function gridRequest(array $query): TestResponse
{
    return getJson(route('admin.settings.roles.index', $query), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);
}

function gridPage(array $pagination): TestResponse
{
    return gridRequest(['pagination' => $pagination]);
}

it('rejects page sizes outside the supported range', function (int|string $perPage): void {
    gridPage(['per_page' => $perPage, 'page' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pagination.per_page');
})->with([0, -5, 51, 100000, 'abc']);

it('rejects invalid page numbers', function (int|string $page): void {
    gridPage(['per_page' => 10, 'page' => $page])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pagination.page');
})->with([0, -1, 'abc']);

it('accepts every page size the UI offers', function (int $perPage): void {
    gridPage(['per_page' => $perPage, 'page' => 1])->assertOk();
})->with(DataGrid::PER_PAGE_OPTIONS);

it('still works without pagination parameters', function (): void {
    gridRequest([])->assertOk();
});

it('rejects a sort direction other than asc or desc', function (): void {
    gridRequest(['sort' => ['column' => 'id', 'order' => 'sideways']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort.order');
});

it('rejects a scalar filter value and an export without a format', function (): void {
    gridRequest(['filters' => ['id' => 1]])->assertUnprocessable()->assertJsonValidationErrors('filters.id');

    gridRequest(['export' => 1])->assertUnprocessable()->assertJsonValidationErrors('format');
});
