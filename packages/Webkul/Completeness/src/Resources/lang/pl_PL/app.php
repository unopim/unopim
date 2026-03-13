<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Pełność',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Pełność zaktualizowana pomyślnie',
                    'title'               => 'Pełność',
                    'configure'           => 'Skonfiguruj pełność',
                    'channel-required'    => 'Wymagane w kanałach',
                    'save-btn'            => 'Zapisz',
                    'back-btn'            => 'Wróć',
                    'mass-update-success' => 'Pełność zaktualizowana pomyślnie',

                    'datagrid' => [
                        'code'             => 'Kod',
                        'name'             => 'Nazwa',
                        'channel-required' => 'Wymagane w kanałach',

                        'actions' => [
                            'change-requirement' => 'Zmień wymaganie pełności',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Brak ustawienia',
                    'completeness'                 => 'Pełność',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Pełność',
                    'subtitle' => 'Średnia pełność',
                ],

                'required-attributes' => 'brakujące wymagane atrybuty',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Obliczone produkty',

                'suggestion' => [
                    'low'     => 'Niska pełność — dodaj szczegóły, aby poprawić.',
                    'medium'  => 'Kontynuuj, dodawaj dalej informacje.',
                    'high'    => 'Prawie kompletne, pozostało tylko kilka szczegółów.',
                    'perfect' => 'Informacje o produkcie są w pełni kompletne.',
                ],
            ],
        ],
    ],
];
