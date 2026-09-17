<?php

use Webkul\Publication\Contracts\LotReleaseResolver;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationRelease;
use Webkul\Publication\Services\NullLotReleaseResolver;
use Webkul\Publication\Services\Publisher;

it('binds the null lot resolver by default so qualified scans behave like unqualified ones', function (): void {
    expect(resolve(LotReleaseResolver::class))->toBeInstanceOf(NullLotReleaseResolver::class);

    [, , $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    $live = '/p/'.$publication->uuid.'/'.$versions[0]->locale->code;

    $this->get('/01/4006381333931/10/LOT-1')->assertRedirect($live);
    $this->get('/01/4006381333931/21/SN0001')->assertRedirect($live);
    $this->get('/01/4006381333931/10/LOT-1/21/SN0001')->assertRedirect($live);
});

it('routes a scanned lot to the release the bound resolver names', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    app()->bind(LotReleaseResolver::class, fn () => new class implements LotReleaseResolver
    {
        public function resolve(Publication $publication, ?string $lot, ?string $serial): ?PublicationRelease
        {
            return $lot === 'L1' ? $publication->releases()->where('sequence', 1)->first() : null;
        }
    });

    $this->get('/01/4006381333931/10/L1')
        ->assertRedirect('/p/'.$publication->uuid.'/r/1/'.$versions[0]->locale->code)
        ->assertHeader('Vary', 'Accept-Language');

    // Unknown lot: the resolver answers null, so the scan lands on the live passport rather than a dead end.
    $this->get('/01/4006381333931/10/UNKNOWN')
        ->assertRedirect('/p/'.$publication->uuid.'/'.$versions[0]->locale->code);
});

it('404s a qualifier outside the GS1 grammar instead of guessing', function (): void {
    $this->publishGtinPassport('4006381333931');

    $this->get('/01/4006381333931/10/'.str_repeat('A', 21))->assertNotFound();
    $this->get('/01/4006381333931/21/bad%7Cpipe')->assertNotFound();
});

it('keeps resolving a gtin the publication carried before a correction', function (): void {
    [$product, $channels, $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    $gtinCode = array_key_first(array_filter($product->values['common'], fn ($value): bool => $value === '4006381333931'));

    $product->values = array_replace_recursive($product->values, ['common' => [$gtinCode => '10614141000415']]);
    $product->save();

    $corrected = resolve(Publisher::class)->publish($product, $channels[0], $versions[0]->locale, 'dpp');

    expect($corrected)->not->toBeNull()
        ->and($publication->fresh()->gtin)->toBe('10614141000415')
        ->and($publication->gtins()->pluck('gtin')->all())->toEqualCanonicalizing(['4006381333931', '10614141000415']);

    $live = '/p/'.$publication->uuid.'/'.$versions[0]->locale->code;

    $this->get('/01/10614141000415')->assertRedirect($live);
    $this->get('/01/4006381333931')->assertRedirect($live);
});
