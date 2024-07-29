<?php

namespace Webkul\Product\Type;

class Simple extends AbstractType
{
    /**
     * Show quantity box.
     *
     * @var bool
     */
    protected $showQuantityBox = true;

    /**
     * Return true if this product type is saleable. Saleable check added because
     * this is the point where all parent product will recall this.
     *
     * @return bool
     */
    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        return $this->haveSufficientQuantity(1);
    }

    /**
     * Have sufficient quantity.
     */
    public function haveSufficientQuantity(int $qty): bool
    {
        if (! $this->product->manage_stock) {
            return true;
        }

        return $qty <= $this->totalQuantity() ?: (bool) core()->getConfigData('catalog.inventory.stock_options.back_orders');
    }

    /**
     * Get product maximum price.
     *
     * @return float
     */
    public function getMaximumPrice()
    {
        return $this->product->price;
    }
}
