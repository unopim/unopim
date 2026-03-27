<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Полнота',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Полнота успешно обновлена',
                    'title'               => 'Полнота',
                    'configure'           => 'Настроить полноту',
                    'channel-required'    => 'Обязательно в каналах',
                    'save-btn'            => 'Сохранить',
                    'back-btn'            => 'Назад',
                    'mass-update-success' => 'Полнота успешно обновлена',
                    'datagrid'            => [
                        'code'             => 'Код',
                        'name'             => 'Название',
                        'channel-required' => 'Обязательно в каналах',
                        'actions'          => [
                            'change-requirement' => 'Изменить требование полноты',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Н/Д',
                    'completeness'                 => 'Завершено',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Полнота',
                    'subtitle' => 'Средняя полнота',
                ],
                'required-attributes' => 'отсутствующие обязательные атрибуты',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Расчёт полноты завершён',
        'completeness-calculated'        => 'Полнота рассчитана для :count продуктов.',
        'completeness-calculated-family' => 'Полнота рассчитана для :count продуктов в семействе ":family".',
        'email-subject'                  => 'Расчёт полноты завершён',
        'email-greeting'                 => 'Здравствуйте,',
        'email-body'                     => 'Расчёт полноты завершён для :count продуктов.',
        'email-body-family'              => 'Расчёт полноты завершён для :count продуктов в семействе атрибутов ":family".',
        'email-footer'                   => 'Вы можете просмотреть детали полноты на своей панели управления.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Рассчитанные продукты',
                'suggestion'          => [
                    'low'     => 'Низкая полнота, добавьте детали для улучшения.',
                    'medium'  => 'Продолжайте, добавляйте информацию.',
                    'high'    => 'Почти завершено, осталось лишь несколько деталей.',
                    'perfect' => 'Информация о продукте полностью заполнена.',
                ],
            ],
        ],
    ],
];
