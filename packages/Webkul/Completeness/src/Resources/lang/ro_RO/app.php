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
                    'update-success'      => 'Completitudine actualizată cu succes',
                    'title'               => 'Completitudine',
                    'configure'           => 'Configurați completitudinea',
                    'channel-required'    => 'Necesar în canale',
                    'save-btn'            => 'Salvare',
                    'back-btn'            => 'Înapoi',
                    'mass-update-success' => 'Completitudine actualizată cu succes',
                    'datagrid'            => [
                        'code'             => 'Cod',
                        'name'             => 'Nume',
                        'channel-required' => 'Necesar în canale',
                        'actions'          => [
                            'change-requirement' => 'Modificați cerința de completitudine',
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
                    'title'    => 'Completitudine',
                    'subtitle' => 'Completitudine medie',
                ],
                'required-attributes' => 'atribute obligatorii lipsă',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Calculul completitudinii finalizat',
        'completeness-calculated'        => 'Completitudine calculată pentru :count produse.',
        'completeness-calculated-family' => 'Completitudine calculată pentru :count produse în familia ":family".',
        'email-subject'                  => 'Calculul completitudinii finalizat',
        'email-greeting'                 => 'Bună ziua,',
        'email-body'                     => 'Calculul completitudinii a fost finalizat pentru :count produse.',
        'email-body-family'              => 'Calculul completitudinii a fost finalizat pentru :count produse în familia de atribute ":family".',
        'email-footer'                   => 'Puteți vizualiza detaliile completitudinii pe panoul de control.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produse calculate',
                'suggestion'          => [
                    'low'     => 'Completitudine scăzută, adăugați detalii pentru a îmbunătăți.',
                    'medium'  => 'Continuați, adăugați în continuare informații.',
                    'high'    => 'Aproape complet, mai sunt doar câteva detalii.',
                    'perfect' => 'Informațiile despre produs sunt complet finalizate.',
                ],
            ],
        ],
    ],

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Culorile scorului de completitudine',
            'info'            => 'Setați scorurile la care indicatorii de completitudine devin verzi sau roșii.',
            'green-from'      => 'Verde de la (%)',
            'green-from-info' => 'Scorurile egale cu această valoare sau mai mari sunt afișate cu verde.',
            'red-below'       => 'Roșu sub (%)',
            'red-below-info'  => 'Scorurile sub această valoare sunt afișate cu roșu. Cele intermediare sunt afișate cu galben.',
            'order-error'     => 'Pragul pentru verde trebuie să fie mai mare decât pragul pentru roșu.',
        ],
    ],
];
