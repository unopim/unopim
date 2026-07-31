<?php

use Illuminate\Support\Str;
use Webkul\Core\Models\CoreConfig;

it('serves an SVG QR carrier for a published passport', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/carrier.svg');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');

    // Cache-Control isn't asserted: the global NoCacheMiddleware merges no-store over the controller's value.
    expect($response->getContent())->toContain('<svg');
});

it('404s a carrier for an unknown uuid', function (): void {
    $this->publishedPassportFixture();

    $this->get('/p/'.Str::uuid().'/carrier.svg')->assertNotFound();
});

it('404s a carrier when the channel public tier is disabled', function (): void {
    $version = $this->publishedPassportFixture();

    CoreConfig::query()
        ->where('code', 'general.publication.settings.enabled')
        ->update(['value' => '0']);

    $this->get('/p/'.$version->publication->uuid.'/carrier.svg')->assertNotFound();
});
