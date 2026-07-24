<?php

use Webkul\Publication\Services\Publisher;

it('renders every payload section and links documents through the proxy', function (): void {
    $version = $this->publishedPassportFixture(withCertificate: true);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee(trans('passport::app.public.sections.passport'))
        ->assertSee(route('publication.public.dpp.asset', [
            'uuid' => $version->publication->uuid,
            'path' => $version->payload['documents'][0]['path'],
        ]), false);
});

it('offers a locale switcher for every channel locale', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    foreach ($version->publication->channel->locales as $locale) {
        $response->assertSee('/p/'.$version->publication->uuid.'/'.$locale->code, false);
    }
});

it('escapes hostile field values instead of rendering them', function (): void {
    // Set the hostile operator before the fixture publishes, so it is baked
    // into the first (and only) version: getCoreConfig() memoizes per request,
    // so a same-request re-publish would read the pre-config value and dedupe.
    $this->setPassportConfig(['operator_name' => '<script>alert(1)</script>']);

    $version = $this->publishedPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('never uses the unescaped blade echo', function (): void {
    $contents = file_get_contents(base_path('packages/Webkul/ProductPassport/src/Resources/views/public/passport.blade.php'));

    expect($contents)->not->toContain('{!!');
});

it('suppresses all payload content once the passport is withdrawn', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk()->assertSee('Recycled cotton, 80%');

    resolve(Publisher::class)->withdraw($version->publication);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee(trans('publication::app.public.withdrawn.heading'))
        ->assertDontSee('Recycled cotton, 80%')
        ->assertDontSee(trans('passport::app.public.identifier.title'))
        ->assertDontSee(trans('passport::app.public.documents.title'));
});

it('sets a restrictive csp and referrer policy on the public route group', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertHeader('Referrer-Policy', 'no-referrer');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'none'")
        ->toContain("frame-ancestors 'none'");
});
