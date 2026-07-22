<?php

use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\StubPayloadBuilder;

beforeEach(function (): void {
    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => StubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'required_group'  => 'dpp_group',
        'route_prefix'    => 'dpp',
    ]);
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
