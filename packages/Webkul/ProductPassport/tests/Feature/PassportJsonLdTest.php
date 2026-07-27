<?php

use Webkul\Publication\Services\Publisher;

it('serves schema.org Product JSON-LD when the client negotiates it', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->withHeaders(['Accept' => 'application/ld+json'])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/ld+json');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($response->json('@context'))->toBe('https://schema.org');
    expect($response->json('@type'))->toBe('Product');
});

it('still serves HTML by default', function (): void {
    $version = $this->publishedPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});

it('does not serve JSON-LD content for a withdrawn passport', function (): void {
    $version = $this->publishedPassportFixture();

    resolve(Publisher::class)->withdraw($version->publication);

    $negotiated = $this->withHeaders(['Accept' => 'application/ld+json'])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    // Content negotiation falls through to the HTML tombstone rather than
    // emitting the frozen payload as a machine-readable schema.org graph.
    $negotiated->assertOk();
    expect($negotiated->headers->get('Content-Type'))->not->toContain('application/ld+json');
    $negotiated->assertDontSee('"@context":"https://schema.org"', false);
    $negotiated->assertDontSee('"@type":"Product"', false);

    // The HTML page for a withdrawn passport must not embed the JSON-LD block
    // either — the payload never surfaces as machine-readable content.
    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertDontSee('<script type="application/ld+json">', false)
        ->assertDontSee('"@type":"Product"', false);
});

it('embeds a JSON-LD script block in the HTML passport page', function (): void {
    $version = $this->publishedPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee('<script type="application/ld+json">', false)
        ->assertSee('"@type":"Product"', false);
});
