<?php

use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\User\Models\Admin;

it('nulls the payload but keeps the checksum', function (): void {
    $version = PublicationVersion::factory()->create(['payload' => ['sections' => ['name' => 'leaked NDA text']]]);
    $checksum = $version->checksum;
    $admin = Admin::factory()->create();

    $version->redact($admin->id, 'contained personal data');

    $fresh = $version->fresh();

    expect($fresh->payload)->toBeNull()
        ->and($fresh->checksum)->toBe($checksum)
        ->and($fresh->redacted_at)->not->toBeNull()
        ->and($fresh->redacted_by_id)->toBe($admin->id)
        ->and($fresh->redacted_reason)->toBe('contained personal data');
});

it('refuses to redact the same version twice', function (): void {
    $version = PublicationVersion::factory()->create();
    $admin = Admin::factory()->create();

    $version->redact($admin->id, 'first redaction');

    expect(fn (): mixed => $version->redact($admin->id, 'second attempt'))
        ->toThrow(ImmutableVersionException::class);
});

it('refuses to un-redact a version by touching redacted_at directly', function (): void {
    $version = PublicationVersion::factory()->create();
    $admin = Admin::factory()->create();

    $version->redact($admin->id, 'reason');

    $fresh = $version->fresh();
    $fresh->redacted_at = null;

    expect(fn (): bool => $fresh->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to let any other column ride along with a redaction', function (): void {
    $version = PublicationVersion::factory()->create(['is_current' => true]);

    $version->is_current = false;
    $version->redacted_at = now();
    $version->redacted_by_id = Admin::factory()->create()->id;
    $version->redacted_reason = 'smuggled change';

    expect(fn (): bool => $version->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to update the payload record outside of redaction', function (): void {
    $version = PublicationVersion::factory()->create(['payload' => ['sections' => []]]);

    $record = $version->payloadRecord()->firstOrFail();
    $record->archive_path = 's3://cold/archive.json.gz';

    expect(fn (): bool => $record->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to reverse a redaction on the payload record directly', function (): void {
    $version = PublicationVersion::factory()->create(['payload' => ['sections' => []]]);
    $version->redact(Admin::factory()->create()->id, 'reason');

    $record = $version->payloadRecord()->firstOrFail();
    $record->payload = ['sections' => ['restored' => true]];

    expect(fn (): bool => $record->save())->toThrow(ImmutableVersionException::class);
});

it('refuses to delete a payload record directly', function (): void {
    $version = PublicationVersion::factory()->create(['payload' => ['sections' => []]]);

    $record = $version->payloadRecord()->firstOrFail();

    expect(fn (): ?bool => $record->delete())->toThrow(ImmutableVersionException::class);
});
