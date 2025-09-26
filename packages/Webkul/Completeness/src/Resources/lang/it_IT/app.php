<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completezza',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completezza aggiornata con successo',
                    'title'               => 'Completezza',
                    'configure'           => 'Configura Completezza',
                    'channel-required'    => 'Richiesto nei canali',
                    'save-btn'            => 'Salva',
                    'back-btn'            => 'Indietro',
                    'mass-update-success' => 'Completezza aggiornata con successo',

                    'datagrid' => [
                        'code'             => 'Codice',
                        'name'             => 'Nome',
                        'channel-required' => 'Richiesto nei canali',

                        'actions' => [
                            'change-requirement' => 'Cambia requisito di completezza',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Nessuna impostazione',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Completezza',
                    'subtitle' => 'Completezza media',
                ],

                'required-attributes' => 'attributi obbligatori mancanti',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Prodotti calcolati',

                'suggestion' => [
                    'low'     => 'Completezza bassa — aggiungi dettagli per migliorare.',
                    'medium'  => 'Continua, continua ad aggiungere informazioni.',
                    'high'    => 'Quasi completo, rimangono solo pochi dettagli.',
                    'perfect' => 'Le informazioni sul prodotto sono completamente complete.',
                ],
            ],
        ],
    ],
];
