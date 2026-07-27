<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Publication\Models\PublicationProxy;

// Lives with the ProductPassport suite, not Publication: only the real `dpp`
// payload builder (this package) emits the `identifier.gtin` the GS1 listener
// reads, and `publishGtinPassport` is a ProductPassportTestCase helper — the
// Publication suite publishes through a stub with no identifier block.
it('populates gtin and the canonical GS1 alias on publish and resolves the scanned link', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');

    $publication = $versions[0]->publication->fresh();

    expect($publication->gtin)->toBe('4006381333931')
        ->and($publication->alias_identifier)->toEndWith('/01/4006381333931');

    $this->get('/01/4006381333931')
        ->assertRedirect('/p/'.$publication->uuid.'/'.$versions[0]->locale->code);
});

it('404s an unknown gtin', function (): void {
    $this->get('/01/9999999999994')->assertNotFound();
});

it('resolves to the designated passport channel when one is configured', function (): void {
    [, $channels, $versions] = $this->publishGtinPassport('4006381333931', channelCount: 2);

    CoreConfig::query()->create([
        'code'  => 'general.publication.settings.gs1_passport_channel',
        'value' => $channels[1]->code,
    ]);

    $designated = $versions[1]->publication->fresh();

    $this->get('/01/4006381333931')
        ->assertRedirect('/p/'.$designated->uuid.'/'.$versions[1]->locale->code);

    // The product-scoped GS1 alias lives on exactly one (canonical) publication,
    // never duplicated across the channels sharing this GTIN.
    expect(PublicationProxy::modelClass()::query()->where('gtin', '4006381333931')->whereNotNull('alias_identifier')->count())
        ->toBe(1);
});
