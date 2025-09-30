<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Volledigheid',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Volledigheid succesvol bijgewerkt',
                    'title'               => 'Volledigheid',
                    'configure'           => 'Volledigheid configureren',
                    'channel-required'    => 'Vereist in kanalen',
                    'save-btn'            => 'Opslaan',
                    'back-btn'            => 'Terug',
                    'mass-update-success' => 'Volledigheid succesvol bijgewerkt',

                    'datagrid' => [
                        'code'             => 'Code',
                        'name'             => 'Naam',
                        'channel-required' => 'Vereist in kanalen',

                        'actions' => [
                            'change-requirement' => 'Wijzig voltooiingsvereiste',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Geen instelling',
                    'completeness'                 => 'Volledig',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Volledigheid',
                    'subtitle' => 'Gemiddelde voltooiing',
                ],

                'required-attributes' => 'ontbrekende verplichte attributen',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Berekende producten',

                'suggestion' => [
                    'low'     => 'Lage voltooiing — voeg details toe om te verbeteren.',
                    'medium'  => 'Ga door, blijf informatie toevoegen.',
                    'high'    => 'Bijna voltooid, er blijven nog een paar details over.',
                    'perfect' => 'Productinformatie is volledig compleet.',
                ],
            ],
        ],
    ],
];
