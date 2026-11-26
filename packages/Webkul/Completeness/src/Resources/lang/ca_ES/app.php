<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completesa',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completesa actualitzada correctament',
                    'title'               => 'Completesa',
                    'configure'           => 'Configurar completesa',
                    'channel-required'    => 'Requerit als canals',
                    'save-btn'            => 'Desar',
                    'back-btn'            => 'Enrere',
                    'mass-update-success' => 'Completesa actualitzada correctament',
                    'datagrid'            => [
                        'code'             => 'Codi',
                        'name'             => 'Nom',
                        'channel-required' => 'Requerit als canals',
                        'actions'          => [
                            'change-requirement' => 'Canviar requisit de completesa',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Complet',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Completesa',
                    'subtitle' => 'Completesa mitjana',
                ],
                'required-attributes' => 'atributs requerits que falten',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Càlcul de completesa finalitzat',
        'completeness-calculated'        => 'Completesa calculada per a :count productes.',
        'completeness-calculated-family' => 'Completesa calculada per a :count productes a la família ":family".',
        'email-subject'                  => 'Càlcul de completesa finalitzat',
        'email-greeting'                 => 'Hola,',
        'email-body'                     => 'El càlcul de completesa s\'ha completat per a :count productes.',
        'email-body-family'              => 'El càlcul de completesa s\'ha completat per a :count productes a la família d\'atributs ":family".',
        'email-footer'                   => 'Podeu consultar els detalls de completesa al vostre tauler.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Productes calculats',
                'suggestion'          => [
                    'low'     => 'Completesa baixa, afegiu detalls per millorar.',
                    'medium'  => 'Continueu, seguiu afegint informació.',
                    'high'    => 'Gairebé complet, només falten uns quants detalls.',
                    'perfect' => 'La informació del producte està totalment completa.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Colors de la puntuació de completesa',
            'info'            => 'Defineix les puntuacions a partir de les quals els indicadors de completesa es mostren en verd o en vermell.',
            'green-from'      => 'Verd a partir de (%)',
            'green-from-info' => 'Les puntuacions iguals o superiors a aquest valor es mostren en verd.',
            'red-below'       => 'Vermell per sota de (%)',
            'red-below-info'  => 'Les puntuacions per sota d\'aquest valor es mostren en vermell. Les intermèdies es mostren en ambre.',
            'order-error'     => 'El llindar del verd ha de ser més alt que el llindar del vermell.',
        ],
    ],
];
