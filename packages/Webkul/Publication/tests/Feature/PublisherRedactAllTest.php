<?php

use Illuminate\Support\Facades\Event;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Repositories\PublicationRepository;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\StubPayloadBuilder;
use Webkul\User\Models\Admin;

beforeEach(function (): void {
    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => StubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'required_group'  => 'dpp_group',
        'route_prefix'    => 'dpp',
    ]);
});

it('redacts every current version and flips the publication status to redacted', function (): void {
    Event::fake([PublicationRedacted::class]);

    [$product, $channel, $other, $complete] = $this->seedPassportFixture(completeBoth: true);

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');
    $second = $publisher->publish($product, $channel, $other, 'dpp');

    $admin = Admin::factory()->create();

    $publisher->redactAll($first->publication->fresh(), $admin->id, 'contained personal data');

    $publication = $first->publication->fresh();

    expect($publication->status)->toBe(PublicationStatus::Redacted)
        ->and($first->fresh()->payload)->toBeNull()
        ->and($first->fresh()->redacted_reason)->toBe('contained personal data')
        ->and($second->fresh()->payload)->toBeNull()
        ->and($second->fresh()->redacted_by_id)->toBe($admin->id);

    Event::assertDispatched(PublicationRedacted::class);
});

it('refuses to redact a publication that has no current versions', function (): void {
    [$product, $channel] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $publication = resolve(PublicationRepository::class)
        ->findOrCreateFor($product->id, $channel->id, 'dpp');

    expect(fn (): mixed => $publisher->redactAll($publication, Admin::factory()->create()->id, 'reason'))
        ->toThrow(InvalidPublicationTransitionException::class);
});

it('refuses to redact an already-redacted publication', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    $admin = Admin::factory()->create();

    $publisher->redactAll($first->publication->fresh(), $admin->id, 'first reason');

    expect(fn (): mixed => $publisher->redactAll($first->publication->fresh(), $admin->id, 'second attempt'))
        ->toThrow(InvalidPublicationTransitionException::class);
});
