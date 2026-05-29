<?php

declare(strict_types=1);

namespace Webkul\Admin\Helpers;

use Webkul\Admin\Helpers\Reporting\Product;

class Reporting
{
    /**
     * Create a controller instance.
     */
    public function __construct(
        protected Product $productReporting,
    ) {}
}
