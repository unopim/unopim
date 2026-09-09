<?php

use Illuminate\Support\Facades\Blade;
use Webkul\Attribute\Models\Attribute;

const TRANSLATABLE_XSS_PAYLOAD = "'><img src=x onerror=alert(document.domain)>";

function renderTranslatableField(string $storedName): string
{
    $locale = new stdClass;
    $locale->code = 'en_US';
    $locale->name = 'English';

    return Blade::render(
        '<x-admin::form.translatable-field :locales="$locales" :values="$values" field="name" />',
        ['locales' => [$locale], 'values' => ['en_US' => $storedName]]
    );
}

it('escapes an apostrophe so a stored value cannot close the :values attribute', function () {
    $html = renderTranslatableField(TRANSLATABLE_XSS_PAYLOAD);

    expect($html)->not->toContain('<img src=x onerror=alert(document.domain)>')
        ->and($html)->toContain('\\u0027');
});

it('escapes angle brackets so a stored value cannot open a tag', function () {
    $html = renderTranslatableField('<script>alert(1)</script>');

    expect($html)->not->toContain('<script>alert(1)')
        ->and($html)->toContain('\\u003C');
});

it('does not serve an attribute label raw on the edit page', function () {
    $this->loginWithPermissions('custom', [
        'catalog',
        'catalog.attributes',
        'catalog.attributes.edit',
    ]);

    $attribute = Attribute::factory()->create(['type' => 'text']);

    $this->put(route('admin.catalog.attributes.update', $attribute->id), [
        'code'  => $attribute->code,
        'type'  => 'text',
        'en_US' => ['name' => TRANSLATABLE_XSS_PAYLOAD],
    ])->assertRedirect();

    $html = $this->get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('<img src=x onerror=alert(document.domain)>');
});
