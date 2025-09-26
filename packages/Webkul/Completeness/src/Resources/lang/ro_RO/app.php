<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completitudine',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completitudinea a fost actualizată cu succes',
                    'title'               => 'Completitudine',
                    'configure'           => 'Configurează completitudinea',
                    'channel-required'    => 'Necesar în canale',
                    'save-btn'            => 'Salvează',
                    'back-btn'            => 'Înapoi',
                    'mass-update-success' => 'Completitudinea a fost actualizată cu succes',

                    'datagrid' => [
                        'code'             => 'Cod',
                        'name'             => 'Nume',
                        'channel-required' => 'Necesar în canale',

                        'actions' => [
                            'change-requirement' => 'Schimbă cerința de completitudine',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Nicio setare',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Completitudine',
                    'subtitle' => 'Completitudine medie',
                ],

                'required-attributes' => 'atribute obligatorii lipsă',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produse calculate',

                'suggestion' => [
                    'low'     => 'Completitudine scăzută — adăugați detalii pentru a îmbunătăți.',
                    'medium'  => 'Continuați, continuați să adăugați informații.',
                    'high'    => 'Aproape complet, mai rămân doar câteva detalii.',
                    'perfect' => 'Informațiile despre produs sunt complet finalizate.',
                ],
            ],
        ],
    ],
];
