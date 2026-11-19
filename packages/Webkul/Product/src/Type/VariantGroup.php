<?php

namespace Webkul\Product\Type;

use Webkul\Product\Models\Product;

/**
 * Internal middle node in a 2-level variant tree
 * (configurable → variant_group → simple). Never created directly by a user;
 * materialized when adding a sub-parent group to a configurable product.
 */
class VariantGroup extends AbstractType
{
    /**
     * Copy relationships, including the simple products under this group.
     *
     * @param  Product  $product
     * @return void
     */
    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        if (in_array('variants', config('products.skipAttributesOnCopy', []))) {
            return;
        }

        foreach ($this->product->variants as $variant) {
            $newVariant = $variant->getTypeInstance()->copy();

            $newVariant->parent_id = $product->id;

            $newVariant->save();
        }
    }
}
