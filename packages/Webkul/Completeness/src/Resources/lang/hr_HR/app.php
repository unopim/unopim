<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Potpunost',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Potpunost je uspješno ažurirana',
                    'title'               => 'Potpunost',
                    'configure'           => 'Konfiguriraj potpunost',
                    'channel-required'    => 'Obavezno u kanalima',
                    'save-btn'            => 'Spremi',
                    'back-btn'            => 'Natrag',
                    'mass-update-success' => 'Potpunost je uspješno ažurirana',

                    'datagrid' => [
                        'code'             => 'Šifra',
                        'name'             => 'Naziv',
                        'channel-required' => 'Obavezno u kanalima',

                        'actions' => [
                            'change-requirement' => 'Promijeni zahtjev za potpunost',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Nema postavke',
                    'completeness'                 => 'Potpun',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Potpunost',
                    'subtitle' => 'Prosječna potpunost',
                ],

                'required-attributes' => 'Nedostaju obavezni atributi',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Izračunati proizvodi',

                'suggestion' => [
                    'low'     => 'Niska potpunost — dodajte detalje kako biste poboljšali informacije.',
                    'medium'  => 'Nastavite, dodajte dodatne informacije.',
                    'high'    => 'Gotovo kompletno — ostalo je samo nekoliko detalja.',
                    'perfect' => 'Informacije o proizvodu su potpuno dovršene.',
                ],
            ],
        ],
    ],
];
