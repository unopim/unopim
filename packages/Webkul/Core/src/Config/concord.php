<?php

use Webkul\Admin\Providers\ModuleServiceProvider;
use Webkul\Core\CoreConvention;

return [

    'convention' => CoreConvention::class,

    'modules' => [

        /**
         * Example:
         * VendorA\ModuleX\Providers\ModuleServiceProvider::class,
         * VendorB\ModuleY\Providers\ModuleServiceProvider::class
         */
        ModuleServiceProvider::class,
        Webkul\Attribute\Providers\ModuleServiceProvider::class,
        Webkul\Category\Providers\ModuleServiceProvider::class,
        Webkul\Core\Providers\ModuleServiceProvider::class,
        Webkul\DataTransfer\Providers\ModuleServiceProvider::class,
        Webkul\Notification\Providers\ModuleServiceProvider::class,
        Webkul\Product\Providers\ModuleServiceProvider::class,
        Webkul\User\Providers\ModuleServiceProvider::class,
        Webkul\AdminApi\Providers\ModuleServiceProvider::class,
    ],
];
