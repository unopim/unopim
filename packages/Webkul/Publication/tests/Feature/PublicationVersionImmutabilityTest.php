<?php

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
