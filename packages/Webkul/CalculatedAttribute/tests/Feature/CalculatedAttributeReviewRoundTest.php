<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function calcRoundPayload(array $overrides = []): array
{
    return array_merge([
        'code'              => 'calc_'.Str::random(6),
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'labels'            => ['en_US' => 'Calculated'],
    ], $overrides);
}

it('keeps the package-owned formula column out of the core fillable list', function (): void {
    expect((new Attribute)->getFillable())->not->toContain('formula');
});

it('still stores a formula submitted through the api', function (): void {
    $payload = calcRoundPayload(['formula' => '  upper(sku)  ']);

    $this->withHeaders($this->getAuthenticationHeaders())
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertSuccessful();

    $this->assertDatabaseHas('attributes', ['code' => $payload['code'], 'formula' => 'upper(sku)']);
});

it('still updates a formula through the api and keeps it on a partial update', function (): void {
    $headers = $this->getAuthenticationHeaders();
    $payload = calcRoundPayload();

    $this->withHeaders($headers)->json('POST', route('admin.api.attributes.store'), $payload)->assertSuccessful();

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attributes.update', $payload['code']), ['formula' => 'lower(sku)'])
        ->assertSuccessful();

    $this->assertDatabaseHas('attributes', ['code' => $payload['code'], 'formula' => 'lower(sku)']);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attributes.update', $payload['code']), ['labels' => ['en_US' => 'Renamed']])
        ->assertSuccessful();

    $this->assertDatabaseHas('attributes', ['code' => $payload['code'], 'formula' => 'lower(sku)']);
});

it('refuses a calculated attribute flagged as required, unique or filterable', function (string $flag): void {
    $payload = calcRoundPayload([$flag => 1]);

    $this->withHeaders($this->getAuthenticationHeaders())
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [$flag]]);

    $this->assertDatabaseMissing('attributes', ['code' => $payload['code']]);
})->with(['is_required', 'is_unique', 'is_filterable']);

it('accepts the flags switched off on a calculated attribute', function (): void {
    $payload = calcRoundPayload(['is_required' => 0, 'is_unique' => 0, 'is_filterable' => 0]);

    $this->withHeaders($this->getAuthenticationHeaders())
        ->json('POST', route('admin.api.attributes.store'), $payload)
        ->assertSuccessful();
});

it('refuses the flags on the admin form for a calculated attribute', function (): void {
    $this->loginAsAdmin();

    $payload = calcRoundPayload(['is_required' => 1]);

    $this->postJson(route('admin.catalog.attributes.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('is_required');
});

it('still allows the flags on attributes of other types', function (): void {
    $this->loginAsAdmin();

    $code = 'plain_'.Str::random(6);

    $this->postJson(route('admin.catalog.attributes.store'), ['code' => $code, 'type' => 'text', 'is_required' => 1])
        ->assertStatus(302);

    $this->assertDatabaseHas('attributes', ['code' => $code, 'is_required' => 1]);
});

it('answers whether anything is calculated with one count query and no full load', function (): void {
    Attribute::query()->where('type', Attribute::CALCULATED_FIELD_TYPE)->delete();

    $registry = app(AttributeRegistry::class);
    $registry->flush();

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($registry->hasCalculated())->toBeFalse()
        ->and($registry->calculatedCodes())->toBe([])
        ->and($registry->hasCalculated())->toBeFalse();

    $queries = array_column(DB::getQueryLog(), 'query');

    DB::disableQueryLog();

    expect($queries)->toHaveCount(1)
        ->and($queries[0])->toContain('count(')
        ->and($queries[0])->not->toContain('formula');
});
