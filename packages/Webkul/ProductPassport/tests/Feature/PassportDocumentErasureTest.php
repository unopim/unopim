<?php

use Illuminate\Events\CallQueuedListener;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Webkul\ProductPassport\Listeners\DeletePassportDocumentsOnRedaction;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
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

it('does not copy the documents back when a redacted passport is published again', function (): void {
    $version = $this->publishedPassportFixture(withDocument: true);

    $publication = $version->publication;

    resolve(Publisher::class)->redactAll($publication->fresh(), $this->loginAsAdmin()->id, 'gdpr request');

    Storage::disk(config('filesystems.default'))->put('product-files/dpp_disassembly_guide/guide.pdf', '%PDF-1.4 changed');

    expect(fn () => resolve(Publisher::class)->publish($publication->product, $publication->channel, $version->locale, 'dpp'))
        ->toThrow(InvalidPublicationTransitionException::class);

    expect(Storage::disk(config('publication.asset_disk'))->exists('publication/'.$publication->uuid))->toBeFalse();
});

it('queues the deletion so a storage error cannot fail the committed redaction', function (): void {
    Queue::fake();

    $version = $this->publishedPassportFixture(withDocument: true);

    resolve(Publisher::class)->redactAll($version->publication->fresh(), $this->loginAsAdmin()->id, 'gdpr request');

    expect($version->publication->fresh()->status)->toBe(PublicationStatus::Redacted);

    Queue::assertPushedOn(
        config('publication.queue'),
        CallQueuedListener::class,
        fn (CallQueuedListener $job): bool => $job->class === DeletePassportDocumentsOnRedaction::class,
    );
});

it('fails the queued deletion when the asset disk cannot delete, so it is retried', function (): void {
    $version = $this->publishedPassportFixture(withDocument: true);

    $disk = Storage::disk(config('publication.asset_disk'));

    Storage::set(config('publication.asset_disk'), new class($disk->getDriver(), $disk->getAdapter(), $disk->getConfig()) extends FilesystemAdapter
    {
        public function deleteDirectory($directory)
        {
            return false;
        }
    });

    $listener = resolve(DeletePassportDocumentsOnRedaction::class);

    expect(fn () => $listener->handle(new PublicationRedacted($version->publication, 'gdpr request')))
        ->toThrow(RuntimeException::class)
        ->and($listener->tries)->toBe(3);
});

it('leaves the asset disk alone for a publication type other than the passport', function (): void {
    $version = $this->publishedPassportFixture(withDocument: true);

    $path = $version->payload['documents'][0]['path'];

    $publication = $version->publication;
    $publication->type = 'other';

    resolve(DeletePassportDocumentsOnRedaction::class)->handle(new PublicationRedacted($publication, 'gdpr request'));

    expect(Storage::disk(config('publication.asset_disk'))->exists($path))->toBeTrue();
});
