<?php

use Webkul\Core\Models\Locale;
use Webkul\Publication\Services\Publisher;

it('renders every payload section and links documents through the proxy', function (): void {
    $version = $this->publishedPassportFixture(withDocument: true);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee(trans('passport::app.public.sections.passport'))
        ->assertSee(route('publication.public.dpp.asset', [
            'uuid' => $version->publication->uuid,
            'path' => $version->payload['documents'][0]['path'],
        ]), false);
});

it('offers a locale switcher for the published locales only', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    // Code cannot be a prefix of the published locale's, or assertDontSee matches inside its URL.
    $unpublished = Locale::factory()->create(['code' => 'zz_ZZ']);
    $publication->channel->locales()->attach($unpublished);

    $response = $this->get('/p/'.$publication->uuid.'/'.$version->locale->code);

    $response->assertSee('/p/'.$publication->uuid.'/'.$version->locale->code, false)
        ->assertDontSee('/p/'.$publication->uuid.'/'.$unpublished->code, false);

    $this->get('/p/'.$publication->uuid.'/'.$unpublished->code)->assertNotFound();
});

it('escapes hostile field values instead of rendering them', function (): void {
    // Config before publish so the hostile value is baked in; getCoreConfig() memoizes per request.
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

it('renders a release url with the release banner, a canonical link to the live page and no indexing', function (): void {
    $version = $this->publishedPassportFixture();

    $uuid = $version->publication->uuid;
    $locale = $version->locale->code;
    $live = route('publication.public.dpp.show.locale', ['uuid' => $uuid, 'locale' => $locale]);

    $this->get('/p/'.$uuid.'/r/1/'.$locale)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, noarchive, nofollow')
        ->assertSee('<link rel="canonical" href="'.$live.'">', false)
        ->assertSee(trans('publication::app.public.release.banner', ['sequence' => 1]))
        ->assertSee(trans('publication::app.public.release.current'))
        ->assertSee(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 1, 'locale' => $locale]), false)
        ->assertDontSee('application/ld+json');
});
