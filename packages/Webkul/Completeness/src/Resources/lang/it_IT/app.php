<?php

return [
    'config' => [
        'completeness' => [
            'title'           => 'Completezza',
            'info'            => 'Soglie di colore del punteggio di completezza.',
            'green-from'      => 'Verde da (%)',
            'green-from-info' => 'I punteggi pari o superiori a questa percentuale vengono mostrati in verde.',
            'red-below'       => 'Rosso sotto (%)',
            'red-below-info'  => 'I punteggi inferiori a questa percentuale vengono mostrati in rosso. Quelli intermedi vengono mostrati in ambra.',
            'green-gt-red'    => 'La soglia verde deve essere maggiore della soglia rossa.',
        ],
    ],

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
                    'configure'           => 'Configura completezza',
                    'channel-required'    => 'Richiesto nei canali',
                    'save-btn'            => 'Salva',
                    'back-btn'            => 'Indietro',
                    'mass-update-success' => 'Completezza aggiornata con successo',
                    'datagrid'            => [
                        'code'             => 'Codice',
                        'name'             => 'Nome',
                        'channel-required' => 'Richiesto nei canali',
                        'actions'          => [
                            'change-requirement' => 'Modifica requisito di completezza',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Completo',
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
    'notifications' => [
        'completeness-title'             => 'Calcolo completezza terminato',
        'completeness-calculated'        => 'Completezza calcolata per :count prodotti.',
        'completeness-calculated-family' => 'Completezza calcolata per :count prodotti nella famiglia ":family".',
        'email-subject'                  => 'Calcolo completezza terminato',
        'email-greeting'                 => 'Salve,',
        'email-body'                     => 'Il calcolo della completezza è stato completato per :count prodotti.',
        'email-body-family'              => 'Il calcolo della completezza è stato completato per :count prodotti nella famiglia di attributi ":family".',
        'email-footer'                   => 'Puoi visualizzare i dettagli della completezza nella tua dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Prodotti calcolati',
                'suggestion'          => [
                    'low'     => 'Completezza bassa, aggiungi dettagli per migliorare.',
                    'medium'  => 'Continua così, aggiungi altre informazioni.',
                    'high'    => 'Quasi completo, mancano solo pochi dettagli.',
                    'perfect' => 'Le informazioni del prodotto sono completamente complete.',
                ],
            ],
        ],
    ],
];
