<?php

return [

    'convention' => Webkul\Core\CoreConvention::class,

    'modules' => [

        /**
         * Example:
         * VendorA\ModuleX\Providers\ModuleServiceProvider::class,
         * VendorB\ModuleY\Providers\ModuleServiceProvider::class
         */
        \Webkul\Admin\Providers\ModuleServiceProvider::class,
        \Webkul\Attribute\Providers\ModuleServiceProvider::class,
        \Webkul\Category\Providers\ModuleServiceProvider::class,
        \Webkul\Core\Providers\ModuleServiceProvider::class,
        \Webkul\Tenant\Providers\ModuleServiceProvider::class,
        \Webkul\DataTransfer\Providers\ModuleServiceProvider::class,
        \Webkul\HistoryControl\Providers\ModuleServiceProvider::class,
        \Webkul\Notification\Providers\ModuleServiceProvider::class,
        \Webkul\Product\Providers\ModuleServiceProvider::class,
        \Webkul\User\Providers\ModuleServiceProvider::class,
        \Webkul\MagicAI\Providers\ModuleServiceProvider::class,
        \Webkul\ChannelConnector\Providers\ChannelConnectorServiceProvider::class,
        \Webkul\Shopify\Providers\ModuleServiceProvider::class,
        \Webkul\Salla\Providers\ModuleServiceProvider::class,
        \Webkul\Amazon\Providers\ModuleServiceProvider::class,
        \Webkul\WooCommerce\Providers\ModuleServiceProvider::class,
        \Webkul\Ebay\Providers\ModuleServiceProvider::class,
        \Webkul\Magento2\Providers\ModuleServiceProvider::class,
        \Webkul\Noon\Providers\ModuleServiceProvider::class,
        \Webkul\EasyOrders\Providers\ModuleServiceProvider::class,
    ],
];
