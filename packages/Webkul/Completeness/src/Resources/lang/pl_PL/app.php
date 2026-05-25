<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Kompletność',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Kompletność zaktualizowana pomyślnie',
                    'title'               => 'Kompletność',
                    'configure'           => 'Skonfiguruj kompletność',
                    'channel-required'    => 'Wymagane w kanałach',
                    'save-btn'            => 'Zapisz',
                    'back-btn'            => 'Wstecz',
                    'mass-update-success' => 'Kompletność zaktualizowana pomyślnie',
                    'datagrid'            => [
                        'code'             => 'Kod',
                        'name'             => 'Nazwa',
                        'channel-required' => 'Wymagane w kanałach',
                        'actions'          => [
                            'change-requirement' => 'Zmień wymaganie kompletności',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Kompletny',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Kompletność',
                    'subtitle' => 'Średnia kompletność',
                ],
                'required-attributes' => 'brakujące wymagane atrybuty',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Obliczanie kompletności zakończone',
        'completeness-calculated'        => 'Kompletność obliczona dla :count produktów.',
        'completeness-calculated-family' => 'Kompletność obliczona dla :count produktów w rodzinie ":family".',
        'email-subject'                  => 'Obliczanie kompletności zakończone',
        'email-greeting'                 => 'Witaj,',
        'email-body'                     => 'Obliczanie kompletności zostało zakończone dla :count produktów.',
        'email-body-family'              => 'Obliczanie kompletności zostało zakończone dla :count produktów w rodzinie atrybutów ":family".',
        'email-footer'                   => 'Szczegóły kompletności możesz zobaczyć na swoim panelu.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Obliczone produkty',
                'suggestion'          => [
                    'low'     => 'Niska kompletność, dodaj szczegóły, aby poprawić.',
                    'medium'  => 'Kontynuuj, dodawaj kolejne informacje.',
                    'high'    => 'Prawie kompletny, zostało tylko kilka szczegółów.',
                    'perfect' => 'Informacje o produkcie są w pełni kompletne.',
                ],
            ],
        ],
    ],
];
