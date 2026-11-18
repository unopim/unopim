<?php

use Webkul\User\Tests\Concerns\UserAssertions;

use function Pest\Laravel\getJson;

uses(UserAssertions::class);

beforeEach(function () {
    $this->loginAsAdmin();
});

function gridPage(array $pagination)
{
    return getJson(route('admin.settings.roles.index', ['pagination' => $pagination]), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);
}

it('rejects page sizes outside the supported range', function ($perPage) {
    gridPage(['per_page' => $perPage, 'page' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pagination.per_page');
})->with([0, -5, 51, 100000, 'abc']);

it('rejects invalid page numbers', function ($page) {
    gridPage(['per_page' => 10, 'page' => $page])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pagination.page');
})->with([0, -1, 'abc']);

it('accepts every page size the UI offers', function ($perPage) {
    gridPage(['per_page' => $perPage, 'page' => 1])->assertOk();
})->with([10, 20, 30, 40, 50]);

it('still works without pagination parameters', function () {
    getJson(route('admin.settings.roles.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
});
