<?php

namespace Webkul\Admin\Helpers;

use Webkul\Admin\Helpers\Reporting\Product;

class Reporting
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Product $productReporting,
    ) {}
}
