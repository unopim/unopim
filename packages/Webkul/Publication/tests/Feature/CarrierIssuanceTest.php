<?php

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

it('redirects the release entry url to the strict per-locale url and 404s an unknown release', function (): void {
    $version = $this->publishedPassportFixture();

    $uuid = $version->publication->uuid;
    $locale = $version->locale->code;

    $this->get('/p/'.$uuid.'/r/1')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 1, 'locale' => $locale]))
        ->assertHeader('Vary', 'Accept-Language');

    $this->get('/p/'.$uuid.'/r/9')->assertNotFound();
});

it('negotiates the entry locale only among locales that exist in that release', function (): void {
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

    // Release 1 predates the other locale: the preference cannot be honoured, so it falls back to what exists.
    $this->withHeaders($preferOther)->get('/p/'.$uuid.'/r/1')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 1, 'locale' => $first->locale->code]));

    $this->withHeaders($preferOther)->get('/p/'.$uuid.'/r/2')
        ->assertRedirect(route('publication.public.dpp.show.release', ['uuid' => $uuid, 'sequence' => 2, 'locale' => $other->code]));
});
