<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Measurement\DataGrids\MeasurementProductDataGrid;

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
    $this->loginWithPermissions('all');

    expect(app(ProductDataGrid::class))->toBeInstanceOf(MeasurementProductDataGrid::class);
});
