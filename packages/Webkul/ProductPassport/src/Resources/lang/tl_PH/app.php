<?php

return [
    'type' => [
        'label' => 'Digital na Pasaporte ng Produkto',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Pasaporte ng Produkto',
            'info'     => 'Mga setting ng paglalathala ng digital na pasaporte ng produkto.',
            'settings' => [
                'title'                  => 'Mga Setting ng Pasaporte ng Produkto',
                'enabled'                => 'Pinagana',
                'auto-publish'           => 'Awtomatikong ilathala kapag na-save',
                'completeness-threshold' => 'Threshold ng Pagkakumpleto (%)',
                'operator-name'          => 'Pangalan ng Economic Operator',
                'operator-address'       => 'Address ng Economic Operator',
                'operator-eu-rep'        => 'Awtorisadong Kinatawan sa EU',
                'support-url'            => 'URL ng Suporta',
            ],
        ],
    ],
];
