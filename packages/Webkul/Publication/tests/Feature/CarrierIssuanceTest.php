<?php

use Webkul\Publication\Exceptions\CarrierNotIssuableException;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Services\CarrierIssuer;
use Webkul\Publication\Services\Publisher;

it('records exactly what a release carrier encodes and refuses to change it afterwards', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $issuance = resolve(CarrierIssuer::class)->issue($publication, $version->release, $this->loginAsAdmin()->id);

    expect($issuance->target)->toBe(rtrim((string) config('app.url'), '/').'/p/'.$publication->uuid.'/r/1')
        ->and($issuance->release_id)->toBe($version->release_id)
        ->and($issuance->format)->toBe('svg')
        ->and($issuance->issued_by_id)->not->toBeNull()
        ->and($publication->carrierIssuances()->count())->toBe(1);

    expect(fn (): mixed => $issuance->update(['target' => 'https://elsewhere.test/']))->toThrow(ImmutableVersionException::class)
        ->and(fn (): mixed => $issuance->delete())->toThrow(ImmutableVersionException::class);
});

it('refuses in the issuer itself to issue a carrier for a withdrawn or redacted publication', function (string $state): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $admin = $this->loginAsAdmin();

    $publisher = resolve(Publisher::class);

    $state === 'withdrawn'
        ? $publisher->withdraw($publication)
        : $publisher->redactAll($publication, $admin->id, 'gdpr request');

    $thrown = null;

    try {
        resolve(CarrierIssuer::class)->issue($publication, $version->release, $admin->id);
    } catch (CarrierNotIssuableException $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(CarrierNotIssuableException::class)
        ->and($thrown->releaseRedacted)->toBeFalse()
        ->and($publication->carrierIssuances()->count())->toBe(0);
})->with(['withdrawn', 'redacted']);

it('refuses in the issuer itself to issue a carrier for a release whose own version is redacted', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $admin = $this->loginAsAdmin();

    $version->redact($admin->id, 'erased on request');

    $thrown = null;

    try {
        resolve(CarrierIssuer::class)->issue($publication->refresh(), $version->release, $admin->id);
    } catch (CarrierNotIssuableException $exception) {
        $thrown = $exception;
    }

    expect($publication->status->value)->toBe('published')
        ->and($thrown)->toBeInstanceOf(CarrierNotIssuableException::class)
        ->and($thrown->releaseRedacted)->toBeTrue()
        ->and($publication->carrierIssuances()->count())->toBe(0);
});

it('redirects the release entry url to the strict per-locale url and 404s an unknown release', function (): void {
    $version = $this->publishedPassportFixture();

    $uuid = $version->publication->uuid;
    $locale = $version->locale->code;

    $this->get('/p/'.$uuid.'/r/1')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 1, 'locale' => $locale]))
        ->assertHeader('Vary', 'Accept-Language');

    $this->get('/p/'.$uuid.'/r/9')->assertNotFound();
});

it('negotiates the entry locale only among locales that exist in that release, falling back when the preferred one came later', function (): void {
    $first = $this->publishedPassportFixture();

    $publication = $first->publication;
    $other = $publication->channel->locales()->where('locales.id', '!=', $first->locale_id)->firstOrFail();

    $product = $publication->product;
    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$other->code => ['dpp_material_composition' => 'aluminium']],
    ]);
    $product->save();

    resolve(Publisher::class)->publish($product, $publication->channel, $other, 'dpp');

    $uuid = $publication->uuid;
    $preferOther = ['Accept-Language' => str_replace('_', '-', $other->code)];

    $this->withHeaders($preferOther)->get('/p/'.$uuid.'/r/1')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 1, 'locale' => $first->locale->code]));

    $this->withHeaders($preferOther)->get('/p/'.$uuid.'/r/2')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 2, 'locale' => $other->code]));
});
