<?php

use Webkul\Attribute\Models\Attribute;

function optionCodesFor(int $attributeId, string $query): array
{
    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.options.fetch-all', [
            'entityName'  => 'attribute',
            'attributeId' => $attributeId,
            'query'       => $query,
            'perPage'     => 100,
        ]));

    $response->assertOk();

    return collect($response->json('options'))->pluck('code')->all();
}

function colourAttributeId(): int
{
    $attribute = Attribute::where('code', 'color')->first();

    expect($attribute)->not->toBeNull();

    return $attribute->id;
}

it('finds a select attribute option whatever case is typed', function (string $query) {
    $this->loginAsAdmin();

    expect(optionCodesFor(colourAttributeId(), $query))->toContain('Red');
})->with([
    'lower case' => 'red',
    'title case' => 'Red',
    'upper case' => 'RED',
]);

it('finds a select attribute option by a partial code', function () {
    $this->loginAsAdmin();

    expect(optionCodesFor(colourAttributeId(), 're'))->toContain('Red');
});

it('returns no option for a term that matches nothing', function () {
    $this->loginAsAdmin();

    expect(optionCodesFor(colourAttributeId(), 'zzzznomatchzzzz'))->toBeEmpty();
});
