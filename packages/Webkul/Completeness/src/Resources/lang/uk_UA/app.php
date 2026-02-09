<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Повнота',
            ],
        ],
    ],

    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Повноту успішно оновлено',
                    'title'               => 'Повнота',
                    'configure'           => 'Налаштувати повноту',
                    'channel-required'    => 'Потрібно в каналах',
                    'save-btn'            => 'Зберегти',
                    'back-btn'            => 'Назад',
                    'mass-update-success' => 'Повноту успішно оновлено',

                    'datagrid' => [
                        'code'             => 'Код',
                        'name'             => 'Назва',
                        'channel-required' => 'Потрібно в каналах',

                        'actions' => [
                            'change-requirement' => 'Змінити вимогу повноти',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Немає налаштувань',
                    'completeness'                 => 'Повнота',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Повнота',
                    'subtitle' => 'Середня повнота',
                ],

                'required-attributes' => 'відсутні обов’язкові атрибути',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Розраховані продукти',

                'suggestion' => [
                    'low'     => 'Низька повнота — додайте деталі, щоб покращити.',
                    'medium'  => 'Продовжуйте, продовжуйте додавати інформацію.',
                    'high'    => 'Майже готово, залишилося лише кілька деталей.',
                    'perfect' => 'Інформація про продукт повністю завершена.',
                ],
            ],
        ],
    ],
];
