<?php

return [
    'simple'       => [
        'key'   => 'simple',
        'name'  => 'product::app.type.simple',
        'class' => 'Webkul\Product\Type\Simple',
        'sort'  => 1,
    ],

    'configurable' => [
        'key'   => 'configurable',
        'name'  => 'product::app.type.configurable',
        'class' => 'Webkul\Product\Type\Configurable',
        'sort'  => 2,
    ],
];
