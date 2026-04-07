<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Volledigheid',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Volledigheid succesvol bijgewerkt',
                    'title'               => 'Volledigheid',
                    'configure'           => 'Volledigheid configureren',
                    'channel-required'    => 'Vereist in kanalen',
                    'save-btn'            => 'Opslaan',
                    'back-btn'            => 'Terug',
                    'mass-update-success' => 'Volledigheid succesvol bijgewerkt',
                    'datagrid'            => [
                        'code'             => 'Code',
                        'name'             => 'Naam',
                        'channel-required' => 'Vereist in kanalen',
                        'actions'          => [
                            'change-requirement' => 'Volledigheidsvereiste wijzigen',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/B',
                    'completeness'                 => 'Volledig',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Volledigheid',
                    'subtitle' => 'Gemiddelde volledigheid',
                ],
                'required-attributes' => 'ontbrekende verplichte attributen',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Volledigheidsberekening voltooid',
        'completeness-calculated'        => 'Volledigheid berekend voor :count producten.',
        'completeness-calculated-family' => 'Volledigheid berekend voor :count producten in familie ":family".',
        'email-subject'                  => 'Volledigheidsberekening voltooid',
        'email-greeting'                 => 'Hallo,',
        'email-body'                     => 'De volledigheidsberekening is voltooid voor :count producten.',
        'email-body-family'              => 'De volledigheidsberekening is voltooid voor :count producten in attribuutfamilie ":family".',
        'email-footer'                   => 'U kunt de volledigheidsdetails bekijken op uw dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Berekende producten',
                'suggestion'          => [
                    'low'     => 'Lage volledigheid, voeg details toe om te verbeteren.',
                    'medium'  => 'Ga door, blijf informatie toevoegen.',
                    'high'    => 'Bijna volledig, nog maar een paar details over.',
                    'perfect' => 'Productinformatie is volledig compleet.',
                ],
            ],
        ],
    ],
];
