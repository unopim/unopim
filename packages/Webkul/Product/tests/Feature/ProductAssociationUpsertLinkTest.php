<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->associationRepository = app(ProductAssociationRepository::class);
    $this->associationTypeRepository = app(AssociationTypeRepository::class);
});

it('inserts a single link via upsertLink', function () {
    $source = Product::factory()->create();
    $target = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->upsertLink(
        $source->id,
        $associationType->id,
        $target->id,
        null,
        ['common' => ['quantity' => '2']]
    );

    $links = $this->associationRepository->getLinksForProduct($source->id);

    expect($links)->toHaveCount(1)
        ->and($links->first()->related_product_id)->toBe($target->id)
        ->and($links->first()->additional_data)->toBe(['common' => ['quantity' => '2']]);
});

it('updates the same row (no duplicate) when upsertLink is called again for the same link', function () {
    $source = Product::factory()->create();
    $target = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->upsertLink(
        $source->id,
        $associationType->id,
        $target->id,
        null,
        ['common' => ['quantity' => '2']]
    );

    $this->associationRepository->upsertLink(
        $source->id,
        $associationType->id,
        $target->id,
        null,
        ['common' => ['quantity' => '5']]
    );

    $links = $this->associationRepository->getLinksForProduct($source->id);

    expect($links)->toHaveCount(1)
        ->and($links->first()->related_product_id)->toBe($target->id)
        ->and($links->first()->additional_data)->toBe(['common' => ['quantity' => '5']]);
});

it('does not prune existing links when upserting a second related product under the same type', function () {
    $source = Product::factory()->create();
    $targetA = Product::factory()->create();
    $targetB = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->upsertLink(
        $source->id,
        $associationType->id,
        $targetA->id,
        null,
        ['common' => ['quantity' => '2']]
    );

    $this->associationRepository->upsertLink(
        $source->id,
        $associationType->id,
        $targetB->id,
        null,
        ['common' => ['quantity' => '3']]
    );

    $links = $this->associationRepository->getLinksForProduct($source->id)->keyBy('related_product_id');

    expect($links)->toHaveCount(2)
        ->and($links->get($targetA->id)->additional_data)->toBe(['common' => ['quantity' => '2']])
        ->and($links->get($targetB->id)->additional_data)->toBe(['common' => ['quantity' => '3']]);
});

it('deletes only the targeted link via deleteLink, leaving other links intact', function () {
    $source = Product::factory()->create();
    $targetA = Product::factory()->create();
    $targetB = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->upsertLink($source->id, $associationType->id, $targetA->id, null, null);
    $this->associationRepository->upsertLink($source->id, $associationType->id, $targetB->id, null, null);

    $this->associationRepository->deleteLink($source->id, $associationType->id, $targetA->id);

    $links = $this->associationRepository->getLinksForProduct($source->id);

    expect($links)->toHaveCount(1)
        ->and($links->first()->related_product_id)->toBe($targetB->id);
});

it('deleteLink is a no-op when the targeted link does not exist', function () {
    $source = Product::factory()->create();
    $target = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->deleteLink($source->id, $associationType->id, $target->id);

    expect($this->associationRepository->getLinksForProduct($source->id))->toHaveCount(0);
});
