<?php

use Illuminate\Database\QueryException;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Models\PublicationVersion;

it('refuses to update a published payload', function (): void {
    $version = PublicationVersion::factory()->create();

    $version->payload = ['tampered' => true];

    expect(fn (): bool => $version->save())->toThrow(ImmutableVersionException::class);
});

it('allows the current pointer to be flipped', function (): void {
    $version = PublicationVersion::factory()->create(['is_current' => true]);

    $version->markSuperseded();

    expect($version->fresh()->is_current)->toBeFalse();
});

it('refuses to delete a product that still has an attested publication', function (): void {
    $version = PublicationVersion::factory()->create();

    $product = $version->publication->product;

    expect(fn (): bool => (bool) $product->delete())->toThrow(QueryException::class);

    expect(PublicationVersion::query()->find($version->id))->not->toBeNull();
});
