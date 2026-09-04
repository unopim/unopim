<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Publication\Services\Publisher;

it('mints a new sealed version when a referenced document is re-issued', function (): void {
    $first = $this->publishedPassportFixture(withDocument: true);

    $publication = $first->publication;
    $firstPath = $first->payload['documents'][0]['path'];

    // Same catalog path, new bytes: what a re-issued declaration of conformity looks like to the PIM.
    Storage::disk(config('filesystems.default'))->put('product-files/dpp_disassembly_guide/guide.pdf', '%PDF-1.4 re-issued');

    $second = resolve(Publisher::class)->publish($publication->product, $publication->channel, $first->locale, 'dpp');

    $assets = Storage::disk(config('publication.asset_disk'));

    expect($second)->not->toBeNull()
        ->and($second->version)->toBe($first->version + 1)
        ->and($second->payload['documents'][0]['path'])->not->toBe($firstPath)
        ->and($assets->get($firstPath))->toBe('%PDF-1.4 stub')
        ->and($assets->get($second->payload['documents'][0]['path']))->toBe('%PDF-1.4 re-issued');
});

it('mints nothing while the referenced document is unchanged', function (): void {
    $first = $this->publishedPassportFixture(withDocument: true);

    $publication = $first->publication;

    expect(resolve(Publisher::class)->publish($publication->product, $publication->channel, $first->locale, 'dpp'))
        ->toBeNull();
});
