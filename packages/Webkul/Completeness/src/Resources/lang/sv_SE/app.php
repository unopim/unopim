<?php

return [
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

                    'datagrid' => [
                        'code'             => 'Kod',
                        'name'             => 'Namn',
                        'channel-required' => 'Krävs i kanaler',

                        'actions' => [
                            'change-requirement' => 'Ändra fullständighetskrav',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ingen inställning',
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

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beräknade produkter',

                'suggestion' => [
                    'low'     => 'Låg fullständighet — lägg till detaljer för att förbättra.',
                    'medium'  => 'Fortsätt, fortsätt att lägga till information.',
                    'high'    => 'Nästan klart, bara några få detaljer återstår.',
                    'perfect' => 'Produktinformationen är helt komplett.',
                ],
            ],
        ],
    ],
];
