<?php

return [
    'type' => [
        'label' => 'Digital Product Passport',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Product Passport',
            'info'     => 'Digital Product Passport publishing settings.',
            'settings' => [
                'title'                  => 'Product Passport Settings',
                'enabled'                => 'Enabled',
                'auto-publish'           => 'Publish automatically on save',
                'completeness-threshold' => 'Completeness Threshold (%)',
                'operator-name'          => 'Economic Operator Name',
                'operator-address'       => 'Economic Operator Address',
                'operator-eu-rep'        => 'EU Authorised Representative',
                'support-url'            => 'Support URL',
            ],
        ],
    ],
];
