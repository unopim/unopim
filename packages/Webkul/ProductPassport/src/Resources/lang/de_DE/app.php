<?php

return [
    'type' => [
        'label' => 'Digitaler Produktpass',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Veröffentlichungseinstellungen für den digitalen Produktpass.',
            'settings' => [
                'title'                  => 'Produktpass-Einstellungen',
                'enabled'                => 'Aktiviert',
                'auto-publish'           => 'Beim Speichern automatisch veröffentlichen',
                'completeness-threshold' => 'Vollständigkeitsschwelle (%)',
                'operator-name'          => 'Name des Wirtschaftsakteurs',
                'operator-address'       => 'Anschrift des Wirtschaftsakteurs',
                'operator-eu-rep'        => 'Bevollmächtigter in der EU',
                'support-url'            => 'Support-URL',
            ],
        ],
    ],
];
