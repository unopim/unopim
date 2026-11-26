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

it('renders a WYSIWYG-sourced field as real HTML instead of escaped literal tags', function (): void {
    $version = $this->publishedRichTextPassportFixture('<p>Hand wash only.</p><ul><li>Do not bleach</li></ul>');

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee('<p>Hand wash only.</p>', false)
        ->assertSee('<ul><li>Do not bleach</li></ul>', false)
        ->assertDontSee('&lt;p&gt;Hand wash only.&lt;/p&gt;', false);
});

it('still purifies a hostile WYSIWYG payload even though rich-text fields render as HTML', function (): void {
    $version = $this->publishedRichTextPassportFixture(
        '<p onclick="steal()">Hand wash only.</p><script>alert(document.cookie)</script><img src=x onerror=alert(1)>'
    );

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk()
        ->assertSee('<p>Hand wash only.</p>', false)
        ->assertDontSee('<script>', false)
        ->assertDontSee('onclick', false)
        ->assertDontSee('onerror', false);
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
