<?php

use Webkul\Product\Enums\ProductTypeEnum;
use Webkul\Product\Type\Configurable;
use Webkul\Product\Type\Simple;
use Webkul\Product\Type\VariantGroup;

return [
    ProductTypeEnum::Simple->value       => [
        'key'   => ProductTypeEnum::Simple->value,
        'name'  => 'product::app.type.simple',
        'class' => Simple::class,
        'sort'  => 1,
    ],

    ProductTypeEnum::Configurable->value => [
        'key'   => ProductTypeEnum::Configurable->value,
        'name'  => 'product::app.type.configurable',
        'class' => Configurable::class,
        'sort'  => 2,
    ],

    ProductTypeEnum::VariantGroup->value => [
        'key'      => ProductTypeEnum::VariantGroup->value,
        'name'     => 'product::app.type.variant-group',
        'class'    => VariantGroup::class,
        'sort'     => 3,
        'internal' => true,
    ],
];
