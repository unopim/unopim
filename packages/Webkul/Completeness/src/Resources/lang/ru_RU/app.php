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
                    'channel-required'    => 'Требуется в каналах',
                    'save-btn'            => 'Сохранить',
                    'back-btn'            => 'Назад',
                    'mass-update-success' => 'Полнота успешно обновлена',

                    'datagrid' => [
                        'code'             => 'Код',
                        'name'             => 'Название',
                        'channel-required' => 'Требуется в каналах',

                        'actions' => [
                            'change-requirement' => 'Изменить требование полноты',
                        ],
                    ],
                ],
            ],
        ],

        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Нет настроек',
                    'completeness'                 => 'Полнота',
                ],
            ],

            'edit' => [
                'completeness' => [
                    'title'    => 'Полнота',
                    'subtitle' => 'Средняя полнота',
                ],

                'required-attributes' => 'отсутствуют обязательные атрибуты',
            ],
        ],
    ],

    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Рассчитанные продукты',

                'suggestion' => [
                    'low'     => 'Низкая полнота — добавьте детали для улучшения.',
                    'medium'  => 'Продолжайте, продолжайте добавлять информацию.',
                    'high'    => 'Почти готово, осталось всего несколько деталей.',
                    'perfect' => 'Информация о продукте полностью заполнена.',
                ],
            ],
        ],
    ],
];
