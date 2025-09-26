<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Integritat',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Integritat actualitzada correctament',
                    'title'               => 'Integritat',
                    'configure'           => 'Configura la integritat',
                    'channel-required'    => 'Requerit en canals',
                    'save-btn'            => 'Desa',
                    'back-btn'            => 'Enrere',
                    'mass-update-success' => 'Integritat actualitzada correctament',

                    'datagrid' => [
                        'code'             => 'Codi',
                        'name'             => 'Nom',
                        'channel-required' => 'Requerit en canals',

                        'actions' => [
                            'change-requirement' => 'Canvia el requisit d\'integritat',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Sense configuració',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Integritat',
                    'subtitle' => 'Mitjana de completitud',
                ],

                'required-attributes' => 'Falten els atributs obligatoris',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Productes calculats',

                'suggestion' => [
                    'low'     => 'Integritat baixa, afegeix més detalls per millorar.',
                    'medium'  => 'Segueix endavant, continua afegint informació.',
                    'high'    => 'Quasi complet, només falten unes quantes dades.',
                    'perfect' => 'La informació del producte està completa.',
                ],
            ],
        ],
    ],

    // Preserve existing settings translations that were present earlier
    'settings' => [
        'edit' => [
            'title' => 'Atributs d\'integritat',
        ],
        'index' => [
            'title'            => 'Integritat',
            'configure'        => 'Configura la integritat',
            'channel-required' => 'Requerit als canals',
        ],
    ],
];
