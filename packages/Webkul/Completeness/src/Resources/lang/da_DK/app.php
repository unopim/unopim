<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Fuldførelse',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Fuldførelse opdateret korrekt',
                    'title'               => 'Fuldførelse',
                    'configure'           => 'Konfigurer fuldførelse',
                    'channel-required'    => 'Påkrævet i kanaler',
                    'save-btn'            => 'Gem',
                    'back-btn'            => 'Tilbage',
                    'mass-update-success' => 'Fuldførelse opdateret korrekt',

                    'datagrid' => [
                        'code'             => 'Kode',
                        'name'             => 'Navn',
                        'channel-required' => 'Påkrævet i kanaler',

                        'actions' => [
                            'change-requirement' => 'Skift fuldførelseskrav',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ingen indstilling',
                    'completeness'                 => 'Fuldført',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Fuldførelse',
                    'subtitle' => 'Gennemsnitlig fuldførelse',
                ],

                'required-attributes' => 'Manglende påkrævede attributter',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beregnet produkter',

                'suggestion' => [
                    'low'     => 'Lav fuldførelse — tilføj flere oplysninger for at forbedre.',
                    'medium'  => 'Fortsæt, tilføj flere oplysninger.',
                    'high'    => 'Næsten færdig — kun få detaljer mangler.',
                    'perfect' => 'Produktinformationen er fuldstændig komplet.',
                ],
            ],
        ],
    ],
];
