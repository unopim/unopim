<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Fuldstændighed',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Fuldstændighed opdateret',
                    'title'               => 'Fuldstændighed',
                    'configure'           => 'Konfigurer fuldstændighed',
                    'channel-required'    => 'Påkrævet i kanaler',
                    'save-btn'            => 'Gem',
                    'back-btn'            => 'Tilbage',
                    'mass-update-success' => 'Fuldstændighed opdateret',
                    'datagrid'            => [
                        'code'             => 'Kode',
                        'name'             => 'Navn',
                        'channel-required' => 'Påkrævet i kanaler',
                        'actions'          => [
                            'change-requirement' => 'Skift krav til fuldstændighed',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Ikke tilgængelig',
                    'completeness'                 => 'Komplet',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Fuldstændighed',
                    'subtitle' => 'Gennemsnitlig fuldstændighed',
                ],
                'required-attributes' => 'manglende påkrævede attributter',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Fuldstændighedsberegning afsluttet',
        'completeness-calculated'        => 'Fuldstændighed beregnet for :count produkter.',
        'completeness-calculated-family' => 'Fuldstændighed beregnet for :count produkter i familien ":family".',
        'email-subject'                  => 'Fuldstændighedsberegning afsluttet',
        'email-greeting'                 => 'Hej,',
        'email-body'                     => 'Fuldstændighedsberegningen er blevet gennemført for :count produkter.',
        'email-body-family'              => 'Fuldstændighedsberegningen er blevet gennemført for :count produkter i attributfamilien ":family".',
        'email-footer'                   => 'Du kan se fuldstændighedsdetaljerne på dit dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Beregnede produkter',
                'suggestion'          => [
                    'low'     => 'Lav fuldstændighed, tilføj detaljer for at forbedre.',
                    'medium'  => 'Fortsæt, bliv ved med at tilføje information.',
                    'high'    => 'Næsten komplet, kun et par detaljer mangler.',
                    'perfect' => 'Produktinformationen er fuldstændig.',
                ],
            ],
        ],
    ],
];
