<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Fullstendighet',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Fullstendighet oppdatert',
                    'title'               => 'Fullstendighet',
                    'configure'           => 'Konfigurer fullstendighet',
                    'channel-required'    => 'Påkrevd i kanaler',
                    'save-btn'            => 'Lagre',
                    'back-btn'            => 'Tilbake',
                    'mass-update-success' => 'Fullstendighet oppdatert',

                    'datagrid' => [
                        'code'             => 'Kode',
                        'name'             => 'Navn',
                        'channel-required' => 'Påkrevd i kanaler',

                        'actions' => [
                            'change-requirement' => 'Endre fullstendighetskrav',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ingen innstilling',
                    'completeness'                 => 'Fullstendig',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Fullstendighet',
                    'subtitle' => 'Gjennomsnittlig fullstendighet',
                ],

                'required-attributes' => 'manglende påkrevde attributter',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beregnet produkter',

                'suggestion' => [
                    'low'     => 'Lav fullstendighet — legg til detaljer for å forbedre.',
                    'medium'  => 'Fortsett, fortsett å legge til informasjon.',
                    'high'    => 'Nesten fullført, det gjenstår bare noen få detaljer.',
                    'perfect' => 'Produktinformasjonen er fullstendig fullført.',
                ],
            ],
        ],
    ],
];
