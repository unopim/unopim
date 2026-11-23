<?php

use Webkul\Attribute\Models\Attribute;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function apiTemplateAttributeData(string $code, array $overrides = []): array
{
    return [
        'code'              => $code,
        'type'              => 'text',
        'validation'        => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        ...$overrides,
    ];
}

it('creates an attribute with templates enabled through the REST API', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attributes.store'), apiTemplateAttributeData('api_tpl_enabled', ['enable_templates' => 1]))
        ->assertCreated();

    expect((int) Attribute::where('code', 'api_tpl_enabled')->value('enable_templates'))->toBe(1);
});

it('rejects templates on attributes that cannot hold them through the REST API', function (array $overrides) {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attributes.store'), apiTemplateAttributeData('api_tpl_invalid', ['enable_templates' => 1, ...$overrides]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.enable_templates.0', trans('admin::app.catalog.attributes.create.templates-unsupported'));

    expect(Attribute::where('code', 'api_tpl_invalid')->exists())->toBeFalse();
})->with([
    'price'      => [['type' => 'price']],
    'validation' => [['validation' => 'email']],
    'unique'     => [['is_unique' => 1]],
]);

it('rejects enabling templates on an attribute with input validation through the REST API', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => 'email', 'is_unique' => 0]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.attributes.update', $attribute->code), ['enable_templates' => 1])
        ->assertUnprocessable()
        ->assertJsonPath('errors.enable_templates.0', trans('admin::app.catalog.attributes.create.templates-unsupported'));

    expect((int) $attribute->fresh()->enable_templates)->toBe(0);
});

it('rejects adding input validation to an attribute that holds templates through the REST API', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => null, 'is_unique' => 0, 'enable_templates' => 1]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.attributes.update', $attribute->code), ['validation' => 'email'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.enable_templates.0', trans('admin::app.catalog.attributes.create.templates-unsupported'));

    expect($attribute->fresh()->validation)->toBeNull();
});
