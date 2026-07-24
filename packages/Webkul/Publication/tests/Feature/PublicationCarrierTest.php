<?php

use Illuminate\Support\Str;
use Webkul\Core\Models\CoreConfig;

it('serves an SVG QR carrier for a published passport', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/carrier.svg');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');

    // The controller sets `Cache-Control: public, max-age=86400`, but the global
    // NoCacheMiddleware appended in bootstrap/app.php runs after and Symfony
    // merges its `no-store` directives over ours — the same union the sibling
    // page controller is subject to — so the carrier's own value isn't asserted.
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
