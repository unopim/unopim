<?php

use Illuminate\Database\QueryException;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;

it('refuses to reassign a published payload', function (): void {
    $version = PublicationVersion::factory()->create();

    expect(fn (): mixed => $version->payload = ['tampered' => true])
        ->toThrow(ImmutableVersionException::class);
});

it('refuses to change a sealed column such as checksum', function (): void {
    $version = PublicationVersion::factory()->create();

    $version->checksum = str_repeat('a', 64);

    expect(fn (): bool => $version->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to change version after publish', function (): void {
    $version = PublicationVersion::factory()->create();

    $version->version = $version->version + 1;

    expect(fn (): bool => $version->save())->toThrow(ImmutableVersionException::class);
});

it('allows the current pointer to be flipped', function (): void {
    $version = PublicationVersion::factory()->create(['is_current' => true]);

    $version->markSuperseded();

    expect($version->fresh()->is_current)->toBeFalse();
});

it('refuses to flip is_current alongside any sealed column', function (): void {
    $version = PublicationVersion::factory()->create(['is_current' => true]);

    $version->is_current = false;
    $version->checksum = str_repeat('b', 64);

    expect(fn (): bool => $version->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to delete a published version', function (): void {
    $version = PublicationVersion::factory()->create();

    expect(fn (): ?bool => $version->delete())->toThrow(ImmutableVersionException::class);
});

it('refuses to delete a publication that still has attested versions', function (): void {
    $version = PublicationVersion::factory()->create();

    expect(fn (): ?bool => $version->publication->delete())->toThrow(ImmutableVersionException::class);

    expect(PublicationVersion::query()->find($version->id))->not->toBeNull();
});

it('allows deleting a publication that has no versions', function (): void {
    $publication = Publication::factory()->create();

    expect($publication->delete())->toBeTrue();
    expect(Publication::query()->find($publication->id))->toBeNull();
});

it('round-trips the payload through gzip storage on a freshly loaded instance', function (): void {
    $version = PublicationVersion::factory()->create(['payload' => ['sections' => ['a' => 1]]]);

    expect($version->fresh()->payload)->toBe(['sections' => ['a' => 1]]);
});

it('refuses to delete a product that still has an attested publication', function (): void {
    $version = PublicationVersion::factory()->create();

    $product = $version->publication->product;

    try {
        $product->delete();

        $this->fail('Expected deleting a product with an attested publication to raise a QueryException.');
    } catch (QueryException $exception) {
        expect($exception->getCode())->toBe('23000')
            ->and($exception->getMessage())->toContain('publications_product_id_foreign');
    }

    expect(PublicationVersion::query()->find($version->id))->not->toBeNull();
});
