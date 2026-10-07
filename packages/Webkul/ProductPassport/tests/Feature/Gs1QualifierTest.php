<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Publication\Contracts\LotReleaseResolver;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Listeners\SyncPublicationGtin;
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
});

it('lands a lot the bound resolver does not know on the live passport rather than a dead end', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    app()->bind(LotReleaseResolver::class, fn () => new class implements LotReleaseResolver
    {
        public function resolve(Publication $publication, ?string $lot, ?string $serial): ?PublicationRelease
        {
            return null;
        }
    });

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

it('resolves a retired gtin to the publication that carried it most recently', function (int $firstOffset, int $secondOffset, int $expected): void {
    [$product, $channels, $versions] = $this->publishGtinPassport('4006381333931', 2);
    $publications = [$versions[0]->publication->fresh(), $versions[1]->publication->fresh()];

    $gtinCode = array_key_first(array_filter($product->values['common'], fn ($value): bool => $value === '4006381333931'));

    $product->values = array_replace_recursive($product->values, ['common' => [$gtinCode => '10614141000415']]);
    $product->save();

    foreach ([0, 1] as $index) {
        resolve(Publisher::class)->publish($product, $channels[$index], $versions[$index]->locale, 'dpp');
    }

    $base = now()->startOfSecond();

    foreach ([0 => $firstOffset, 1 => $secondOffset] as $index => $offset) {
        DB::table('publication_gtins')
            ->where('publication_id', $publications[$index]->id)
            ->where('gtin', '4006381333931')
            ->update(['recorded_at' => $base->copy()->addSeconds($offset)]);
    }

    $this->get('/01/4006381333931')
        ->assertRedirect('/p/'.$publications[$expected]->uuid.'/'.$versions[$expected]->locale->code);
})->with([
    'later on the higher channel'    => [-3600, 0, 1],
    'later on the lower channel'     => [0, -3600, 0],
    'same instant, highest id wins'  => [0, 0, 1],
]);

it('stops resolving a revoked gtin from the history', function (): void {
    [$product, $channels, $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    $gtinCode = array_key_first(array_filter($product->values['common'], fn ($value): bool => $value === '4006381333931'));

    $product->values = array_replace_recursive($product->values, ['common' => [$gtinCode => '10614141000415']]);
    $product->save();

    resolve(Publisher::class)->publish($product, $channels[0], $versions[0]->locale, 'dpp');

    $publication->gtins()->where('gtin', '4006381333931')->first()->revoke();

    $this->get('/01/4006381333931')->assertNotFound();
    $this->get('/01/10614141000415')->assertRedirect('/p/'.$publication->uuid.'/'.$versions[0]->locale->code);
});

it('keeps a revoked history row append-only apart from the revocation itself', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');

    $row = $versions[0]->publication->fresh()->gtins()->first();

    expect(fn () => $row->update(['gtin' => '10614141000415']))->toThrow(ImmutableVersionException::class)
        ->and(fn () => $row->delete())->toThrow(ImmutableVersionException::class);

    $row = $row->fresh();
    $row->revoke();

    expect($row->fresh()->revoked_at)->not->toBeNull()
        ->and(fn () => $row->fresh()->forceFill(['revoked_at' => null])->save())->toThrow(ImmutableVersionException::class);
});

it('revokes a retired gtin through the artisan command', function (): void {
    [$product, $channels, $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    $gtinCode = array_key_first(array_filter($product->values['common'], fn ($value): bool => $value === '4006381333931'));

    $product->values = array_replace_recursive($product->values, ['common' => [$gtinCode => '10614141000415']]);
    $product->save();

    resolve(Publisher::class)->publish($product, $channels[0], $versions[0]->locale, 'dpp');

    $this->artisan('unopim:publication:revoke-gtin', ['gtin' => '4006381333931'])->assertSuccessful();

    expect($publication->gtins()->where('gtin', '4006381333931')->first()->revoked_at)->not->toBeNull()
        ->and($publication->gtins()->where('gtin', '10614141000415')->first()->revoked_at)->toBeNull();

    $this->get('/01/4006381333931')->assertNotFound();
});

it('refuses to revoke a gtin a publication still carries', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');

    $this->artisan('unopim:publication:revoke-gtin', ['gtin' => '4006381333931'])->assertFailed();

    expect($versions[0]->publication->fresh()->gtins()->first()->revoked_at)->toBeNull();
});

it('rejects a malformed gtin in the revoke command', function (): void {
    $this->artisan('unopim:publication:revoke-gtin', ['gtin' => 'not-a-gtin'])->assertFailed();
});

it('does not resolve a serial containing an encoded slash, a known limitation of the route grammar', function (): void {
    $this->publishGtinPassport('4006381333931');

    $this->get('/01/4006381333931/21/A%2F1')->assertNotFound();
});

it('skips the gtin writes when a publish carries the gtin the publication already has', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931');
    $publication = $versions[0]->publication->fresh();

    DB::enableQueryLog();

    resolve(SyncPublicationGtin::class)->handle(new PublicationPublished($publication, $versions[0]));

    $writes = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $sql): bool => (str_contains($sql, 'publication_gtins') && ! str_starts_with($sql, 'select')) || preg_match('/^update .publications. set .gtin. =/i', $sql) === 1);

    expect($writes)->toBeEmpty()
        ->and($publication->gtins()->count())->toBe(1);
});

it('warns and falls back to live when the lot resolver returns a release of another publication', function (): void {
    [, , $versions] = $this->publishGtinPassport('4006381333931', 2);
    $publication = $versions[0]->publication->fresh();
    $foreign = $versions[1]->publication->fresh()->releases()->where('sequence', 1)->first();

    app()->bind(LotReleaseResolver::class, fn () => new class($foreign) implements LotReleaseResolver
    {
        public function __construct(private readonly PublicationRelease $release) {}

        public function resolve(Publication $publication, ?string $lot, ?string $serial): ?PublicationRelease
        {
            return $this->release;
        }
    });

    Log::spy();

    $this->get('/01/4006381333931/10/L1')
        ->assertRedirect('/p/'.$publication->uuid.'/'.$versions[0]->locale->code);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'another publication') && $context['release_id'] === $foreign->id)
        ->once();
});
