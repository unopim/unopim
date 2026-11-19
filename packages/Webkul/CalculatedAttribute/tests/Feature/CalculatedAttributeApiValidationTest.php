<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;

/**
 * A formula reaching the database through the REST API without being checked
 * is the failure the admin form's validation alone cannot prevent, so every
 * rejection the form makes is asserted here against the API as well.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calcApiAttributePayload(array $overrides = []): array
{
    return array_merge([
        'code'              => 'calc_'.Str::random(6),
        'type'              => 'calculated',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_required'       => 0,
        'is_unique'         => 0,
        'labels'            => ['en_US' => 'Calculated'],
    ], $overrides);
}

it('refuses a formula that does not parse', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $payload = calcApiAttributePayload(['formula' => "concat('-', sku"]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['formula']]);

    $this->assertDatabaseMissing('attributes', ['code' => $payload['code']]);
});

it('refuses a formula that names an attribute which does not exist', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $payload = calcApiAttributePayload(['formula' => 'upper(attribute_that_does_not_exist)']);

    $response = $this->withHeaders($headers)
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(422);

    expect($response->json('errors.formula.0'))->toContain('attribute_that_does_not_exist');

    $this->assertDatabaseMissing('attributes', ['code' => $payload['code']]);
});

it('refuses a calculated attribute created without a formula', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $payload = calcApiAttributePayload();

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['formula']]);

    $this->assertDatabaseMissing('attributes', ['code' => $payload['code']]);
});

it('accepts a formula that parses and stores it trimmed', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $payload = calcApiAttributePayload(['formula' => '  upper(sku)  ']);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('attributes', [
        'code'    => $payload['code'],
        'formula' => 'upper(sku)',
    ]);
});

it('discards a formula sent for an attribute that is not calculated', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $payload = calcApiAttributePayload([
        'type'    => 'text',
        'formula' => 'upper(sku)',
    ]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(201);

    $this->assertDatabaseHas('attributes', [
        'code'    => $payload['code'],
        'formula' => null,
    ]);
});

it('refuses a formula that closes a cycle on update', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $first = 'calca_'.Str::random(6);
    $second = 'calcb_'.Str::random(6);

    Attribute::factory()->create([
        'code'    => $first,
        'type'    => 'calculated',
        'formula' => 'upper(sku)',
    ]);

    Attribute::factory()->create([
        'code'    => $second,
        'type'    => 'calculated',
        'formula' => "concat('-', {$first})",
    ]);

    app(AttributeRegistry::class)->flush();

    $response = $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attributes.update', $first), [
            'formula' => "concat('-', {$second})",
        ])
        ->assertStatus(422);

    expect($response->json('errors.formula.0'))->toContain('circular');

    $this->assertDatabaseHas('attributes', [
        'code'    => $first,
        'formula' => 'upper(sku)',
    ]);
});

it('refuses a formula that no longer parses on update', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $code = 'calcu_'.Str::random(6);

    Attribute::factory()->create([
        'code'    => $code,
        'type'    => 'calculated',
        'formula' => 'upper(sku)',
    ]);

    app(AttributeRegistry::class)->flush();

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attributes.update', $code), [
            'formula' => 'upper(attribute_that_does_not_exist)',
        ])
        ->assertStatus(422);

    $this->assertDatabaseHas('attributes', [
        'code'    => $code,
        'formula' => 'upper(sku)',
    ]);
});

it('leaves the stored formula alone when an update does not mention it', function (): void {
    $headers = $this->getAuthenticationHeaders();

    $code = 'calcp_'.Str::random(6);

    Attribute::factory()->create([
        'code'        => $code,
        'type'        => 'calculated',
        'formula'     => 'upper(sku)',
        'is_required' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $this->withHeaders($headers)
        ->json('PATCH', route('admin.api.attributes.patch', $code), ['is_required' => 1])
        ->assertOk();

    $this->assertDatabaseHas('attributes', [
        'code'    => $code,
        'formula' => 'upper(sku)',
    ]);
});
