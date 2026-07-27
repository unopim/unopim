<?php

use Illuminate\Support\Facades\URL;

function signedTierUrl(string $uuid, string $locale, string $tier): string
{
    return URL::temporarySignedRoute(
        'publication.public.dpp.show.locale',
        now()->addHour(),
        ['uuid' => $uuid, 'locale' => $locale, 'tier' => $tier],
    );
}

it('excludes operator-tier fields from the public consumer page', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee('Recycled cotton, 80%')
        ->assertDontSee('Tier 2 supplier in Poland');
});

it('reveals operator-tier fields on a valid signed tier url', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $response = $this->get(signedTierUrl($version->publication->uuid, $version->locale->code, 'operator'));

    $response->assertOk()
        ->assertSee('Recycled cotton, 80%')
        ->assertSee('Tier 2 supplier in Poland');

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');
});

it('fails closed on an unsigned tier query param', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code.'?tier=operator')
        ->assertOk()
        ->assertSee('Recycled cotton, 80%')
        ->assertDontSee('Tier 2 supplier in Poland');
});

it('fails closed when the signature is tampered', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $tampered = signedTierUrl($version->publication->uuid, $version->locale->code, 'operator').'x';

    $this->get($tampered)
        ->assertOk()
        ->assertDontSee('Tier 2 supplier in Poland');
});

it('keeps operator field values out of the negotiated consumer json-ld', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $response = $this->withHeaders(['Accept' => 'application/ld+json'])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/ld+json');

    $encoded = $response->getContent();

    expect($encoded)->toContain('Recycled cotton, 80%')
        ->and($encoded)->not->toContain('Tier 2 supplier in Poland');
});

it('reveals operator field values in the negotiated json-ld behind a valid signature', function (): void {
    $version = $this->publishedTieredPassportFixture();

    $response = $this->withHeaders(['Accept' => 'application/ld+json'])
        ->get(signedTierUrl($version->publication->uuid, $version->locale->code, 'operator'));

    $response->assertOk();
    expect($response->getContent())->toContain('Tier 2 supplier in Poland');
});

it('partitions fields into config-driven tier buckets in the built payload', function (): void {
    $payload = $this->publishedTieredPassportFixture()->payload;

    // dpp_material_composition is unmapped => base consumer tier;
    // dpp_supply_chain_notes is mapped to operator, so it must never sit in
    // the consumer bucket the template and JSON-LD read from.
    $consumerCodes = array_column($payload['tiers']['consumer']['fields'], 'code');
    $operatorCodes = array_column($payload['tiers']['operator']['fields'], 'code');

    expect($consumerCodes)->toContain('dpp_material_composition')
        ->and($consumerCodes)->not->toContain('dpp_supply_chain_notes')
        ->and($operatorCodes)->toContain('dpp_supply_chain_notes')
        ->and($payload['sections'][0]['fields'])->toBe($payload['tiers']['consumer']['fields']);
});

it('treats every field as consumer when the tiers map is empty (backward compatible)', function (): void {
    config(['publication.tiers.map' => []]);

    $version = $this->publishedTieredPassportFixture();

    // With no classification, the operator field collapses to the consumer tier
    // and surfaces on the plain public page exactly as it did before tiering.
    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee('Recycled cotton, 80%')
        ->assertSee('Tier 2 supplier in Poland');
});
