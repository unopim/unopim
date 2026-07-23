<?php

use Illuminate\Support\Facades\Event;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationReinstated;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\ListOrderVariantPayloadBuilder;
use Webkul\Publication\Tests\Support\OrderVariantPayloadBuilder;
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

    OrderVariantPayloadBuilder::$order = 'a';
});

it('blocks a locale below the completeness threshold and publishes a complete sibling', function (): void {
    [$product, $channel, $incomplete, $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    expect($publisher->publish($product, $channel, $incomplete, 'dpp'))->toBeNull()
        ->and($publisher->publish($product, $channel, $complete, 'dpp'))
        ->toBeInstanceOf(PublicationVersion::class);
});

it('mints no new version when the payload is unchanged', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($publisher->publish($product, $channel, $complete, 'dpp'))->toBeNull()
        ->and($first->publication->versions()->count())->toBe(1);
});

it('versions each locale independently', function (): void {
    [$product, $channel, $other, $complete] = $this->seedPassportFixture(completeBoth: true);

    $publisher = resolve(Publisher::class);

    $publisher->publish($product, $channel, $complete, 'dpp');
    $publisher->publish($product, $channel, $other, 'dpp');

    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$complete->code => ['dpp_material_composition' => 'changed']],
    ]);
    $product->save();

    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($second->version)->toBe(2)
        ->and($second->publication->currentVersion($other->id)->version)->toBe(1);
});

it('mints a new version but leaves a withdrawn publication withdrawn', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    $publisher->withdraw($first->publication);

    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$complete->code => ['dpp_material_composition' => 'changed after withdrawal']],
    ]);
    $product->save();

    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($second)->toBeInstanceOf(PublicationVersion::class)
        ->and($second->version)->toBe(2)
        ->and($second->publication->fresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('reinstates a withdrawn publication back to published', function (): void {
    Event::fake([PublicationReinstated::class]);

    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    $publisher->withdraw($first->publication);

    $publisher->reinstate($first->publication->fresh());

    expect($first->publication->fresh()->status)->toBe(PublicationStatus::Published);

    Event::assertDispatched(PublicationReinstated::class);
});

it('refuses to reinstate a publication that is not withdrawn', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    expect(fn (): mixed => $publisher->reinstate($first->publication->fresh()))
        ->toThrow(InvalidPublicationTransitionException::class);
});

it('refuses to reinstate a redacted publication: redaction is one-way, not withdrawn', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    $publisher->redactAll($first->publication->fresh(), Admin::factory()->create()->id, 'reason');

    expect(fn (): mixed => $publisher->reinstate($first->publication->fresh()))
        ->toThrow(InvalidPublicationTransitionException::class);
});

it('blocks publishing when a completeness score exists but is below the threshold', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    ProductCompletenessScore::query()
        ->where('product_id', $product->id)
        ->where('channel_id', $channel->id)
        ->where('locale_id', $complete->id)
        ->update(['score' => 50]);

    $publisher = resolve(Publisher::class);

    expect($publisher->publish($product, $channel, $complete, 'dpp'))->toBeNull();
});

it('canonicalises payload key order so reordered but identical payloads mint only one version', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    config()->set('publication.types.dpp.payload_builder', OrderVariantPayloadBuilder::class);

    $publisher = resolve(Publisher::class);

    OrderVariantPayloadBuilder::$order = 'a';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    OrderVariantPayloadBuilder::$order = 'b';
    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($first)->toBeInstanceOf(PublicationVersion::class)
        ->and($second)->toBeNull()
        ->and($first->publication->versions()->count())->toBe(1);
});

it('sorts content lists by stable business key so a shuffled fields list mints only one version', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    config()->set('publication.types.dpp.payload_builder', ListOrderVariantPayloadBuilder::class);

    $publisher = resolve(Publisher::class);

    ListOrderVariantPayloadBuilder::$order = 'a';
    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    ListOrderVariantPayloadBuilder::$order = 'b';
    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($first)->toBeInstanceOf(PublicationVersion::class)
        ->and($second)->toBeNull()
        ->and($first->publication->versions()->count())->toBe(1);
});

it('never resurrects a redacted publication back to published via a routine publish', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    $publisher = resolve(Publisher::class);

    $first = $publisher->publish($product, $channel, $complete, 'dpp');

    $publisher->redactAll($first->publication->fresh(), Admin::factory()->create()->id, 'contained personal data');

    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$complete->code => ['dpp_material_composition' => 'changed after redaction']],
    ]);
    $product->save();

    $second = $publisher->publish($product, $channel, $complete, 'dpp');

    expect($second)->toBeInstanceOf(PublicationVersion::class)
        ->and($second->version)->toBe(2)
        ->and($second->publication->fresh()->status)->toBe(PublicationStatus::Redacted);
});
