<?php

use Webkul\AdminApi\ApiDataSource;

function makeClampDataSource(): ApiDataSource
{
    return new class extends ApiDataSource
    {
        public function prepareApiQueryBuilder()
        {
            return null;
        }

        public function exposeResolvePerPage(mixed $limit): int
        {
            return $this->resolvePerPage($limit);
        }
    };
}

it('caps a huge requested limit at the maximum per page', function () {
    expect(makeClampDataSource()->exposeResolvePerPage(1000000))->toBe(100);
});

it('keeps a valid limit within bounds', function () {
    expect(makeClampDataSource()->exposeResolvePerPage(50))->toBe(50);
});

it('falls back to the default when limit is non-numeric or absent', function () {
    $ds = makeClampDataSource();

    expect($ds->exposeResolvePerPage('abc'))->toBe(10)
        ->and($ds->exposeResolvePerPage(null))->toBe(10);
});

it('floors sub-minimum limits to one', function () {
    $ds = makeClampDataSource();

    expect($ds->exposeResolvePerPage(0))->toBe(1)
        ->and($ds->exposeResolvePerPage(-5))->toBe(1);
});
