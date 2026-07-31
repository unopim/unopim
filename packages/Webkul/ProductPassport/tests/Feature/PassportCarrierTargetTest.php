<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Services\Gs1DigitalLink;

/**
 * A QR carrier is printed on a physical product, so what it encodes has to keep
 * resolving for the life of that product — not only at the moment of publish.
 *
 * BaconQrCode renders the payload as SVG paths with no readable text, so these
 * assert the target through the same service the controller resolves it from,
 * plus that the endpoint itself still renders.
 */
it('resolves the gs1 digital link for a routable gtin', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');

    $publication = $versions[0]->publication->fresh();

    expect(resolve(Gs1DigitalLink::class)->for($publication))->toBe(url('/01/4006381333931'));

    $this->get('/p/'.$publication->uuid.'/carrier')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

it('rebuilds the link from the current base url, not the one stamped at publish', function (): void {
    [, $channels, $versions] = $this->publishGtinPassport('4006381333931');

    $publication = $versions[0]->publication->fresh();

    expect($publication->alias_identifier)->toEndWith('/01/4006381333931');

    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.publication.settings.base_url', 'channel_code' => $channels[0]->code, 'locale_code' => null],
        ['value' => 'https://dpp.example.test'],
    );

    expect(resolve(Gs1DigitalLink::class)->for($publication->fresh()))
        ->toBe('https://dpp.example.test/01/4006381333931');
});

it('has no gs1 link to encode when the gtin cannot route', function (): void {
    [, , $versions] = $this->publishGtinPassport('1010');

    $publication = $versions[0]->publication->fresh();

    expect($publication->gtin)->toBe('1010')
        ->and($publication->alias_identifier)->toBeNull()
        ->and(resolve(Gs1DigitalLink::class)->for($publication))->toBeNull();

    $this->get('/p/'.$publication->uuid.'/carrier')->assertOk();

    $this->get('/01/1010')->assertNotFound();
});

it('leaves the gs1 column empty when no routable link exists', function (): void {
    [, , $versions] = $this->publishGtinPassport('1010');

    $grid = resolve(PublicationDataGrid::class);
    $grid->setQueryBuilder();

    $row = collect($grid->getExportableData())
        ->first(fn (object $export): bool => (int) $export->id === $versions[0]->publication->id);

    expect($row->gs1_link)->toBe('');
});

it('rejects gtins outside the route grammar', function (): void {
    $gs1 = resolve(Gs1DigitalLink::class);

    expect($gs1->isWellFormed('4006381333931'))->toBeTrue()
        ->and($gs1->isWellFormed('1010'))->toBeFalse()
        ->and($gs1->isWellFormed('123456789012345'))->toBeFalse()
        ->and($gs1->isWellFormed('40063813abcde'))->toBeFalse()
        ->and($gs1->isWellFormed(null))->toBeFalse();
});

it('never aliases a gtin the public route cannot match', function (): void {
    $this->publishGtinPassport('1010');

    expect(PublicationProxy::modelClass()::query()->where('gtin', '1010')->whereNotNull('alias_identifier')->count())
        ->toBe(0);
});
