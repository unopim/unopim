<?php

return [
    'config' => [
        'completeness' => [
            'title'           => 'Fullständighet',
            'info'            => 'Färgtrösklar för fullständighetspoäng.',
            'green-from'      => 'Grön från (%)',
            'green-from-info' => 'Poäng på eller över denna procentandel visas i grönt.',
            'red-below'       => 'Röd under (%)',
            'red-below-info'  => 'Poäng under denna procentandel visas i rött. De däremellan visas i bärnsten.',
            'green-gt-red'    => 'Den gröna tröskeln måste vara större än den röda tröskeln.',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Fullständighet',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Fullständighet uppdaterad',
                    'title'               => 'Fullständighet',
                    'configure'           => 'Konfigurera fullständighet',
                    'channel-required'    => 'Krävs i kanaler',
                    'save-btn'            => 'Spara',
                    'back-btn'            => 'Tillbaka',
                    'mass-update-success' => 'Fullständighet uppdaterad',
                    'datagrid'            => [
                        'code'             => 'Kod',
                        'name'             => 'Namn',
                        'channel-required' => 'Krävs i kanaler',
                        'actions'          => [
                            'change-requirement' => 'Ändra fullständighetskrav',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Fullständig',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Fullständighet',
                    'subtitle' => 'Genomsnittlig fullständighet',
                ],
                'required-attributes' => 'saknade obligatoriska attribut',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Fullständighetsberäkning klar',
        'completeness-calculated'        => 'Fullständighet beräknad för :count produkter.',
        'completeness-calculated-family' => 'Fullständighet beräknad för :count produkter i familjen ":family".',
        'email-subject'                  => 'Fullständighetsberäkning klar',
        'email-greeting'                 => 'Hej,',
        'email-body'                     => 'Fullständighetsberäkningen har slutförts för :count produkter.',
        'email-body-family'              => 'Fullständighetsberäkningen har slutförts för :count produkter i attributfamiljen ":family".',
        'email-footer'                   => 'Du kan se fullständighetsdetaljerna på din instrumentpanel.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beräknade produkter',
                'suggestion'          => [
                    'low'     => 'Låg fullständighet, lägg till detaljer för att förbättra.',
                    'medium'  => 'Fortsätt, lägg till mer information.',
                    'high'    => 'Nästan fullständig, bara några detaljer kvar.',
                    'perfect' => 'Produktinformationen är helt fullständig.',
                ],
            ],
        ],
    ],
];
