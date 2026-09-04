<?php

use Illuminate\Support\Str;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Services\Publisher;

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
        ->not->toContain((core()->getAdminEmailDetails()['email'] ?? null) ?: 'unopim@webkul.com')
        ->and($response->getContent())->not->toContain('layouts.anonymous');
});

it('404s everything when the global kill switch is off, regardless of channel setting', function (): void {
    $version = $this->publishedPassportFixture();

    config(['publication.enabled' => false]);

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertNotFound();
});

it('answers 410 Gone, uncacheable and unindexable, for a redacted passport', function (): void {
    $version = $this->publishedPassportFixture();

    resolve(Publisher::class)->redactAll($version->publication->fresh(), $this->loginAsAdmin()->id, 'gdpr request');

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertStatus(410)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee(trans('publication::app.public.withdrawn.heading'));

    expect($response->headers->getCacheControlDirective('no-store'))->toBeTrue()
        ->and($response->headers->getCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse();
});

it('keeps a withdrawn tombstone out of shared caches while still answering 200', function (): void {
    $version = $this->publishedPassportFixture();

    $version->publication->update(['status' => PublicationStatus::Withdrawn]);

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($response->headers->getCacheControlDirective('no-store'))->toBeTrue()
        ->and($response->headers->getCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('s-maxage'))->toBeFalse();
});

it('serves an earlier release at its own url, unindexed, with a canonical link to the live page', function (): void {
    $first = $this->publishedPassportFixture();
    $second = $this->republishWithMaterial($first, 'recycled steel');

    $uuid = $first->publication->uuid;
    $locale = $first->locale->code;
    $live = route('publication.public.dpp.show.locale', ['uuid' => $uuid, 'locale' => $locale]);

    $this->get('/p/'.$uuid.'/r/1/'.$locale)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, noarchive, nofollow')
        ->assertHeader('Link', '<'.$live.'>; rel="canonical"')
        ->assertSee(e(json_encode($first->fresh()->payload)), false)
        ->assertDontSee('recycled steel')
        ->assertSee(trans('publication::app.public.release.banner', ['sequence' => 1]))
        ->assertSee(trans('publication::app.public.release.superseded'));

    $this->get('/p/'.$uuid.'/r/2/'.$locale)
        ->assertOk()
        ->assertSee('recycled steel')
        ->assertSee(trans('publication::app.public.release.current'));

    expect($second->release->sequence)->toBe(2);
});

it('404s a release that does not exist and a sequence the router rejects', function (): void {
    $version = $this->publishedPassportFixture();

    $uuid = $version->publication->uuid;
    $locale = $version->locale->code;

    $this->get('/p/'.$uuid.'/r/9/'.$locale)->assertNotFound();
    $this->get('/p/'.$uuid.'/r/0/'.$locale)->assertNotFound();
    $this->get('/p/'.$uuid.'/r/abc/'.$locale)->assertNotFound();
});

it('answers 410 for a release whose version was redacted, even while the publication stays published', function (): void {
    $first = $this->publishedPassportFixture();
    $this->republishWithMaterial($first, 'recycled steel');

    $first->fresh()->redact($this->loginAsAdmin()->id, 'contained personal data');

    $uuid = $first->publication->uuid;
    $locale = $first->locale->code;

    $response = $this->get('/p/'.$uuid.'/r/1/'.$locale)
        ->assertStatus(410)
        ->assertSee(trans('publication::app.public.withdrawn.heading'))
        ->assertDontSee('material');

    expect($response->headers->getCacheControlDirective('no-store'))->toBeTrue();

    // The live page is unaffected.
    $this->get('/p/'.$uuid.'/'.$locale)->assertOk()->assertSee('recycled steel');
});

it('changes the etag of a release page once that state stops being current', function (): void {
    $first = $this->publishedPassportFixture();

    $uuid = $first->publication->uuid;
    $locale = $first->locale->code;

    $whileCurrent = $this->get('/p/'.$uuid.'/r/1/'.$locale)->assertOk()->headers->get('ETag');

    $this->republishWithMaterial($first, 'recycled steel');

    $onceSuperseded = $this->get('/p/'.$uuid.'/r/1/'.$locale)->assertOk()->headers->get('ETag');

    expect($whileCurrent)->not->toBe($onceSuperseded)
        ->and($this->withHeaders(['If-None-Match' => $onceSuperseded])->get('/p/'.$uuid.'/r/1/'.$locale)->status())->toBe(304);
});

it('resolves a release strictly per locale: a locale first published later is absent from earlier releases', function (): void {
    $first = $this->publishedPassportFixture();

    $publication = $first->publication;
    $other = $publication->channel->locales()->where('locales.id', '!=', $first->locale_id)->firstOrFail();

    $product = $publication->product;
    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$other->code => ['dpp_material_composition' => 'aluminium']],
    ]);
    $product->save();

    $otherVersion = resolve(Publisher::class)->publish($product, $publication->channel, $other, 'dpp');

    expect($otherVersion)->not->toBeNull()
        ->and($otherVersion->release->sequence)->toBe(2);

    $this->get('/p/'.$publication->uuid.'/r/1/'.$other->code)->assertNotFound();
    $this->get('/p/'.$publication->uuid.'/r/2/'.$other->code)->assertOk()->assertSee('aluminium');
    $this->get('/p/'.$publication->uuid.'/r/2/'.$first->locale->code)->assertOk();
});
