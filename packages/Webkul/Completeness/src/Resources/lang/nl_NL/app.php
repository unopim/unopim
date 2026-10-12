<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Datavolledigheid',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Datavolledigheid succesvol bijgewerkt.',
                    'title'               => 'Datavolledigheid',
                    'configure'           => 'Datavolledigheid configureren',
                    'channel-required'    => 'Vereist in kanalen',
                    'save-btn'            => 'Opslaan',
                    'back-btn'            => 'Terug',
                    'mass-update-success' => 'Datavolledigheid succesvol bijgewerkt.',
                    'datagrid'            => [
                        'code'             => 'Code',
                        'name'             => 'Naam',
                        'channel-required' => 'Vereist in kanalen',
                        'actions'          => [
                            'change-requirement' => 'Datavolledigheidsvereiste wijzigen',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/B',
                    'completeness'                 => 'Datavolledigheid',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Datavolledigheid',
                    'subtitle' => 'Gemiddelde datavolledigheid',
                ],
                'required-attributes' => 'ontbrekende verplichte attributen',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Datavolledigheidsberekening voltooid',
        'completeness-calculated'        => 'Datavolledigheid berekend voor :count producten.',
        'completeness-calculated-family' => 'Datavolledigheid berekend voor :count producten in attribuutset ":family".',
        'email-subject'                  => 'Datavolledigheidsberekening voltooid',
        'email-greeting'                 => 'Hallo,',
        'email-body'                     => 'De datavolledigheidsberekening is voltooid voor :count producten.',
        'email-body-family'              => 'De datavolledigheidsberekening is voltooid voor :count producten in attribuutset ":family".',
        'email-footer'                   => 'Je kunt de datavolledigheidsdetails bekijken op je dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Berekende producten',
                'suggestion'          => [
                    'low'     => 'Lage datavolledigheid — voeg details toe om te verbeteren.',
                    'medium'  => 'Goed bezig, voeg meer informatie toe.',
                    'high'    => 'Bijna volledig, nog maar een paar details te gaan.',
                    'perfect' => 'Productinformatie is volledig ingevuld.',
                ],
            ],
        ],
    ],
];
