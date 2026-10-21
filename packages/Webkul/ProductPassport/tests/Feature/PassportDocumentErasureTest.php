<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Publication\Services\Publisher;

it('deletes the document copies of a redacted passport from the asset disk', function (): void {
    $version = $this->publishedPassportFixture(withDocument: true);

    $path = $version->payload['documents'][0]['path'];

    $disk = Storage::disk(config('publication.asset_disk'));

    expect($disk->exists($path))->toBeTrue();

    resolve(Publisher::class)->redactAll($version->publication->fresh(), $this->loginAsAdmin()->id, 'gdpr request');

    expect($disk->exists($path))->toBeFalse()
        ->and($disk->exists('publication/'.$version->publication->uuid))->toBeFalse();
});

it('leaves the document copies of other passports in place on redaction', function (): void {
    $redacted = $this->publishedPassportFixture(withDocument: true);

    $kept = $this->publishedPassportFixture(withDocument: true);

    $keptPath = $kept->payload['documents'][0]['path'];

    resolve(Publisher::class)->redactAll($redacted->publication->fresh(), $this->loginAsAdmin()->id, 'gdpr request');

    expect(Storage::disk(config('publication.asset_disk'))->exists($keptPath))->toBeTrue();
});
