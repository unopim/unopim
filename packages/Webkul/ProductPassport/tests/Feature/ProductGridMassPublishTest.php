<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Services\AttributeValueNormalizer;

function passportMassActionTitles(): array
{
    return collect(app(ProductDataGrid::class)->getMassActions())
        ->map(fn ($massAction): string => $massAction->title)
        ->all();
}

it('adds the mass-publish action to the product grid for a permitted admin', function (): void {
    $this->enablePassportPublishing(core()->getRequestedChannelCode());

    $this->loginWithPermissions('all');

    expect(passportMassActionTitles())->toContain(trans('passport::app.publications.mass-publish.action'));
});

it('omits the mass-publish action for an admin without the publish permission', function (): void {
    $this->enablePassportPublishing(core()->getRequestedChannelCode());

    $this->loginWithPermissions('custom', ['catalog.products']);

    expect(passportMassActionTitles())->not->toContain(trans('passport::app.publications.mass-publish.action'));
});

it('decorates the grid rather than rebinding it, so other packages keep their own subclass', function (): void {
    $this->enablePassportPublishing(core()->getRequestedChannelCode());

    $this->loginWithPermissions('all');

    // Stands in for another package's grid subclass; extending must preserve it.
    $subclass = new class(app(AttributeFamilyRepository::class), app(ProductRepository::class), app(ChannelRepository::class), app(ProductAttributeValuesNormalizer::class), app(AttributeService::class), app(AttributeValueNormalizer::class), app(VariantValueResolver::class)) extends ProductDataGrid {};

    app()->bind(ProductDataGrid::class, fn (): ProductDataGrid => clone $subclass);

    $resolved = app(ProductDataGrid::class);

    expect($resolved)->toBeInstanceOf($subclass::class)
        ->and(collect($resolved->getMassActions())->pluck('title'))
        ->toContain(trans('passport::app.publications.mass-publish.action'));
});
