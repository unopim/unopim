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

it('preserves additional_data on a surviving link when syncFromSkuList re-syncs', function () {
    $source = Product::factory()->create();
    $existingTarget = Product::factory()->create();
    $newTarget = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->create([
        'product_id'          => $source->id,
        'association_type_id' => $associationType->id,
        'related_product_id'  => $existingTarget->id,
        'position'            => null,
        'additional_data'     => ['common' => ['quantity' => '5']],
    ]);

    $this->associationRepository->syncFromSkuList($source->id, 'up_sells', [
        $existingTarget->sku,
        $newTarget->sku,
    ]);

    $links = $this->associationRepository->getLinksForProduct($source->id)
        ->keyBy('related_product_id');

    expect($links)->toHaveCount(2)
        ->and($links->get($existingTarget->id)->additional_data)->toBe(['common' => ['quantity' => '5']])
        ->and($links->get($newTarget->id))->not->toBeNull()
        ->and($links->get($newTarget->id)->additional_data)->toBeNull();
});

it('writes additional_data explicitly via syncTypeWithData', function () {
    $source = Product::factory()->create();
    $target = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->syncTypeWithData($source->id, $associationType->id, [
        [
            'related_product_id' => $target->id,
            'position'           => 1,
            'additional_data'    => ['common' => ['quantity' => '9']],
        ],
    ]);

    $link = $this->associationRepository->getLinksForProduct($source->id)->first();

    expect($link->additional_data)->toBe(['common' => ['quantity' => '9']])
        ->and($link->position)->toBe(1);
});

it('prunes a removed link via syncTypeWithData', function () {
    $source = Product::factory()->create();
    $targetA = Product::factory()->create();
    $targetB = Product::factory()->create();

    $associationType = $this->associationTypeRepository->findByCode('up_sells');

    $this->associationRepository->syncTypeWithData($source->id, $associationType->id, [
        [
            'related_product_id' => $targetA->id,
            'position'           => null,
            'additional_data'    => ['common' => ['quantity' => '1']],
        ],
        [
            'related_product_id' => $targetB->id,
            'position'           => null,
            'additional_data'    => null,
        ],
    ]);

    expect($this->associationRepository->getLinksForProduct($source->id))->toHaveCount(2);

    $this->associationRepository->syncTypeWithData($source->id, $associationType->id, [
        [
            'related_product_id' => $targetA->id,
            'position'           => null,
            'additional_data'    => ['common' => ['quantity' => '1']],
        ],
    ]);

    $links = $this->associationRepository->getLinksForProduct($source->id);

    expect($links)->toHaveCount(1)
        ->and($links->first()->related_product_id)->toBe($targetA->id);
});
