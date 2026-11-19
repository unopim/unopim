<?php

use Webkul\Product\Enums\ProductType;
use Webkul\Product\Type\Configurable;
use Webkul\Product\Type\Simple;
use Webkul\Product\Type\VariantGroup;

return [
    ProductType::Simple->value       => [
        'key'   => ProductType::Simple->value,
        'name'  => 'product::app.type.simple',
        'class' => Simple::class,
        'sort'  => 1,
    ],

    ProductType::Configurable->value => [
        'key'   => ProductType::Configurable->value,
        'name'  => 'product::app.type.configurable',
        'class' => Configurable::class,
        'sort'  => 2,
    ],

    ProductType::VariantGroup->value => [
        'key'      => ProductType::VariantGroup->value,
        'name'     => 'product::app.type.variant-group',
        'class'    => VariantGroup::class,
        'sort'     => 3,
        'internal' => true,
    ],
];
