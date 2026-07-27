<?php

namespace Webkul\Publication\Listeners;

use RuntimeException;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Fails fast, before the delete query ever reaches the RESTRICT foreign key
 * on `publications.product_id`, with a translated message this package
 * wrote on purpose — rather than whatever raw constraint-violation text a
 * catalog delete controller might otherwise echo back to the browser.
 */
class GuardProductDeletionAgainstPublications
{
    public function handle(int|string $productId): void
    {
        if (PublicationProxy::modelClass()::where('product_id', (int) $productId)->exists()) {
            throw new RuntimeException(trans('publication::app.publications.product-delete-blocked'));
        }
    }
}
