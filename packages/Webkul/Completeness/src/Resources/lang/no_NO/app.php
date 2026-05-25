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
                    'datagrid'            => [
                        'code'             => 'Kode',
                        'name'             => 'Navn',
                        'channel-required' => 'Påkrevd i kanaler',
                        'actions'          => [
                            'change-requirement' => 'Endre fullstendighetskrav',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ikke tilgjengelig',
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
    'notifications' => [
        'completeness-title'             => 'Fullstendighetsberegning fullført',
        'completeness-calculated'        => 'Fullstendighet beregnet for :count produkter.',
        'completeness-calculated-family' => 'Fullstendighet beregnet for :count produkter i familien ":family".',
        'email-subject'                  => 'Fullstendighetsberegning fullført',
        'email-greeting'                 => 'Hei,',
        'email-body'                     => 'Fullstendighetsberegningen er fullført for :count produkter.',
        'email-body-family'              => 'Fullstendighetsberegningen er fullført for :count produkter i attributtfamilien ":family".',
        'email-footer'                   => 'Du kan se fullstendighetsdetaljene på dashbordet ditt.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beregnede produkter',
                'suggestion'          => [
                    'low'     => 'Lav fullstendighet, legg til detaljer for å forbedre.',
                    'medium'  => 'Fortsett, fortsett å legge til informasjon.',
                    'high'    => 'Nesten fullstendig, bare noen få detaljer gjenstår.',
                    'perfect' => 'Produktinformasjonen er fullstendig.',
                ],
            ],
        ],
    ],
];
