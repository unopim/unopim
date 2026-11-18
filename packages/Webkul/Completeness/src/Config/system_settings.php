<?php

return [
    [
        'key'    => 'system.completeness',
        'name'   => 'completeness::app.config.completeness.title',
        'info'   => 'completeness::app.config.completeness.info',
        'icon'   => 'icon-attribute',
        'acl'    => 'configuration.system_settings.completeness',
        'sort'   => 6,
        'fields' => [
            [
                'name'          => 'green_from',
                'title'         => 'completeness::app.config.completeness.green-from',
                'type'          => 'number',
                'info'          => 'completeness::app.config.completeness.green-from-info',
                'default_value' => '90',
                'validation'    => 'numeric|min:0|max:100',
            ],
            [
                'name'          => 'red_below',
                'title'         => 'completeness::app.config.completeness.red-below',
                'type'          => 'number',
                'info'          => 'completeness::app.config.completeness.red-below-info',
                'default_value' => '60',
                'validation'    => 'numeric|min:0|max:100',
            ],
        ],
    ],
];
