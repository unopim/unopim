<?php

return [
    [
        'key'    => 'system.measurement',
        'name'   => 'measurement::app.config.catalog.measurement.title',
        'info'   => 'measurement::app.config.catalog.measurement.info',
        'icon'   => 'icon-attribute',
        'acl'    => 'configuration.system_settings',
        'sort'   => 5,
        'fields' => [
            [
                'name'          => 'strategy',
                'title'         => 'measurement::app.config.catalog.measurement.precision.strategy',
                'type'          => 'blade',
                'path'          => 'measurement::configuration.field.precision-strategy',
                'info'          => 'measurement::app.config.catalog.measurement.precision.strategy-info',
                'default_value' => 'round',
            ],
            [
                'name'          => 'amount',
                'title'         => 'measurement::app.config.catalog.measurement.precision.amount',
                'type'          => 'number',
                'info'          => 'measurement::app.config.catalog.measurement.precision.amount-info',
                'default_value' => '4',
                'validation'    => 'numeric|min:0|max_value:10',
            ],
            [
                'name'          => 'base',
                'title'         => 'measurement::app.config.catalog.measurement.precision.base',
                'type'          => 'number',
                'info'          => 'measurement::app.config.catalog.measurement.precision.base-info',
                'default_value' => '6',
                'validation'    => 'numeric|min:0|max_value:10',
            ],
        ],
    ],
];
