<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->loginAsAdmin();
});

it('rejects an unsupported or non-string type and creates no attribute', function (mixed $type): void {
    $code = 'bad_type_'.uniqid();

    postJson(route('admin.catalog.attributes.store'), ['code' => $code, 'type' => $type])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');

    expect(Attribute::where('code', $code)->exists())->toBeFalse();
})->with([
    'unknown string' => 'unknown',
    'empty string'   => '',
    'array'          => [['text']],
    'wrong case'     => 'TEXT',
    'boolean true'   => true,
    'integer'        => 1,
]);

it('still creates an attribute for every supported type', function (): void {
    foreach (array_keys(config('attribute_types')) as $type) {
        $code = 'ok_'.str_replace('-', '_', $type).'_'.uniqid();

        $payload = ['code' => $code, 'type' => $type];

        if (in_array($type, ['select', 'multiselect'], true)) {
            $payload['swatch_type'] = 'text';
        }

        postJson(route('admin.catalog.attributes.store'), $payload)->assertStatus(302);

        expect(Attribute::where('code', $code)->value('type'))->toBe($type);
    }
});
