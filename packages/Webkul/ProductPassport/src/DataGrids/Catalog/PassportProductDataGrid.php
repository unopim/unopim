<?php

namespace Webkul\ProductPassport\DataGrids\Catalog;

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\ProductPassport\Http\Controllers\PublicationController;

/**
 * Adds a passport mass-publish action to the product grid without touching the
 * Admin package. Bound over ProductDataGrid in the service provider, so every
 * product-grid resolution inherits the action while the feature is enabled.
 */
class PassportProductDataGrid extends ProductDataGrid
{
    public function prepareMassActions()
    {
        parent::prepareMassActions();

        if (! PublicationController::featureEnabled() || ! bouncer()->hasPermission('catalog.passport.publish')) {
            return;
        }

        $this->addMassAction([
            'title'  => trans('passport::app.publications.mass-publish.action'),
            'url'    => route('admin.catalog.passports.mass_publish'),
            'method' => 'POST',
        ]);
    }
}
