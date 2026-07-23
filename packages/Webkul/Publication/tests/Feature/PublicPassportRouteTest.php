<?php

use Illuminate\Support\Str;
use Webkul\Publication\Enums\PublicationStatus;

it('redirects the bare uuid to the canonical per-locale url without caching the redirect', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid);

    $response->assertRedirect('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertHeader('Vary', 'Accept-Language');

    // Not an exact-match assertion: bootstrap/app.php's global NoCacheMiddleware
    // runs AFTER this controller returns and unconditionally re-sets
    // Cache-Control — Symfony's ResponseHeaderBag merges cache-control
    // directives across multiple set() calls rather than replacing them, so
    // the final header is the union of ours and NoCacheMiddleware's own
    // (verified: "max-age=0, must-revalidate, no-cache, no-store, private").
    // The security property that matters — this redirect is never cached —
    // holds either way; NoCacheMiddleware's own directives are a strict
    // superset of "no-store, private".
    expect($response->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('private');
});

it('renders the canonical locale url with a cacheable, status-bound etag and sets the app locale', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk()->assertSee('lang="'.$version->locale->code.'"', false);

    expect($response->headers->get('ETag'))->not->toBeEmpty()
        ->and(app()->getLocale())->toBe($version->locale->code)
        ->and($response->headers->getCookies())->toBeEmpty();
});

it('returns 304 when the etag matches', function (): void {
    $version = $this->publishedPassportFixture();

    $etag = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->headers->get('ETag');

    $this->withHeaders(['If-None-Match' => $etag])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertStatus(304);
});

it('changes the etag on withdrawal even though no version row changes, so a cached client is not stuck serving live content', function (): void {
    $version = $this->publishedPassportFixture();

    $etag = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->headers->get('ETag');

    $version->publication->update(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders(['If-None-Match' => $etag])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk();
});

it('renders a tombstone rather than a 404 for a withdrawn passport', function (): void {
    $version = $this->publishedPassportFixture();

    $version->publication->update(['status' => PublicationStatus::Withdrawn]);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertSee(trans('publication::app.public.withdrawn.heading'));
});

it('ignores an arbitrary channel query parameter entirely', function (): void {
    $version = $this->publishedPassportFixture();

    $withParam = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code.'?channel=does-not-exist');
    $withoutParam = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    expect($withParam->status())->toBe($withoutParam->status());
});

it('404s an unknown uuid without leaking the admin error page', function (): void {
    $response = $this->get('/p/'.Str::uuid());

    $response->assertNotFound();

    expect($response->getContent())
        ->not->toContain(core()->getAdminEmailDetails()['email'] ?? 'unopim@webkul.com')
        ->and($response->getContent())->not->toContain('layouts.anonymous');
});

it('404s everything when the global kill switch is off, regardless of channel setting', function (): void {
    $version = $this->publishedPassportFixture();

    config(['publication.enabled' => false]);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertNotFound();
});
