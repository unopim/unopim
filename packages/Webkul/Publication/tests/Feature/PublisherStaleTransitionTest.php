<?php

use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\StubPayloadBuilder;
use Webkul\User\Models\Admin;

beforeEach(function (): void {
    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => StubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'route_prefix'    => 'dpp',
    ]);
});

it('does not let a stale Published model withdraw a publication redacted meanwhile', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $version = $publisher->publish($product, $channel, $complete, 'dpp');

    $stale = $version->publication->fresh();
    expect($stale->status)->toBe(PublicationStatus::Published);

    $publisher->redactAll($version->publication->fresh(), Admin::factory()->create()->id, 'erasure');
    expect($version->publication->fresh()->status)->toBe(PublicationStatus::Redacted);

    expect(fn (): mixed => $publisher->withdraw($stale))
        ->toThrow(InvalidPublicationTransitionException::class);

    expect($version->publication->fresh()->status)->toBe(PublicationStatus::Redacted)
        ->and($stale->status)->toBe(PublicationStatus::Redacted);
});

it('does not let a stale Withdrawn model reinstate a publication redacted meanwhile', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $version = $publisher->publish($product, $channel, $complete, 'dpp');
    $publisher->withdraw($version->publication->fresh());

    $stale = $version->publication->fresh();
    expect($stale->status)->toBe(PublicationStatus::Withdrawn);

    $publisher->redactAll($version->publication->fresh(), Admin::factory()->create()->id, 'erasure');

    expect(fn (): mixed => $publisher->reinstate($stale))
        ->toThrow(InvalidPublicationTransitionException::class);

    expect($version->publication->fresh()->status)->toBe(PublicationStatus::Redacted)
        ->and($stale->status)->toBe(PublicationStatus::Redacted);
});
