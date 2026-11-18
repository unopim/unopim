<?php

return [
    'config' => [
        'completeness' => [
            'title'           => 'Повнота',
            'info'            => 'Колірні пороги оцінки повноти.',
            'green-from'      => 'Зелений від (%)',
            'green-from-info' => 'Оцінки, рівні цьому відсотку або вищі, відображаються зеленим.',
            'red-below'       => 'Червоний нижче (%)',
            'red-below-info'  => 'Оцінки нижче цього відсотка відображаються червоним. Проміжні відображаються бурштиновим.',
            'green-gt-red'    => 'Зелений поріг має бути більшим за червоний поріг.',
        ],
    ],

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
                    'channel-required'    => 'Обов\'язково в каналах',
                    'save-btn'            => 'Зберегти',
                    'back-btn'            => 'Назад',
                    'mass-update-success' => 'Повноту успішно оновлено',
                    'datagrid'            => [
                        'code'             => 'Код',
                        'name'             => 'Назва',
                        'channel-required' => 'Обов\'язково в каналах',
                        'actions'          => [
                            'change-requirement' => 'Змінити вимогу повноти',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Н/А',
                    'completeness'                 => 'Завершено',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Повнота',
                    'subtitle' => 'Середня повнота',
                ],
                'required-attributes' => 'відсутні обов\'язкові атрибути',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Обчислення повноти завершено',
        'completeness-calculated'        => 'Повноту розраховано для :count продуктів.',
        'completeness-calculated-family' => 'Повноту розраховано для :count продуктів у сімействі ":family".',
        'email-subject'                  => 'Обчислення повноти завершено',
        'email-greeting'                 => 'Вітаємо,',
        'email-body'                     => 'Обчислення повноти завершено для :count продуктів.',
        'email-body-family'              => 'Обчислення повноти завершено для :count продуктів у сімействі атрибутів ":family".',
        'email-footer'                   => 'Ви можете переглянути деталі повноти на своїй панелі керування.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Розраховані продукти',
                'suggestion'          => [
                    'low'     => 'Низька повнота, додайте деталі для покращення.',
                    'medium'  => 'Продовжуйте, додавайте більше інформації.',
                    'high'    => 'Майже завершено, залишилось лише кілька деталей.',
                    'perfect' => 'Інформація про продукт повністю заповнена.',
                ],
            ],
        ],
    ],
];
