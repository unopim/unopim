<?php

use Webkul\Attribute\Models\Attribute;

function availableColumnCodes(string $query): array
{
    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.datagrid.available_columns', [
            'entityName' => 'attributes',
            'source'     => 'product',
            'query'      => $query,
            'limit'      => 100,
        ]));

    $response->assertOk();

    return collect($response->json('options'))->pluck('code')->all();
}

it('matches an attribute name regardless of the case typed', function (string $query) {
    $this->loginAsAdmin();

    $attribute = Attribute::where('code', 'url_key')->first();

    expect($attribute)->not->toBeNull();

    expect(availableColumnCodes($query))->toContain('url_key');
})->with([
    'exact case' => 'URL Key',
    'lower case' => 'url key',
    'upper case' => 'URL KEY',
    'partial'    => 'url',
]);

it('matches an attribute by a partial code', function () {
    $this->loginAsAdmin();

    expect(availableColumnCodes('url_k'))->toContain('url_key');
});

it('still returns nothing for a term that matches no attribute', function () {
    $this->loginAsAdmin();

    expect(availableColumnCodes('zzzznomatchzzzz'))->toBeEmpty();
});
