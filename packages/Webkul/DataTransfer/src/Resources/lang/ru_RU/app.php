<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Продукты',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Ключ URL: \'%s\' уже был создан для элемента с SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Недопустимое значение для столбца семейства атрибутов (семейство атрибутов не существует?)',
                    'invalid-type'                             => 'Тип продукта недействителен или не поддерживается.',
                    'sku-not-found'                            => 'Товар с указанным артикулом не найден',
                    'super-attribute-not-found'                => 'Настраиваемый атрибут с кодом :code не найден или не принадлежит к семейству атрибутов :familyCode.',
                    'configurable-attributes-not-found'        => 'Для создания модели продукта необходимы настраиваемые атрибуты.',
                    'configurable-attributes-wrong-type'       => 'Только атрибуты выбранного типа, которые не основаны на локали или канале, могут быть настраиваемыми атрибутами для настраиваемого продукта.',
                    'variant-configurable-attribute-not-found' => 'Вариант настраиваемого атрибута :code создания требуется код',
                    'not-unique-variant-product'               => 'Продукт с такими же настраиваемыми атрибутами уже существует.',
                    'channel-not-exist'                        => 'Этого канала не существует.',
                    'locale-not-in-channel'                    => 'Эта локаль не выбрана в канале.',
                    'locale-not-exist'                         => 'Эта локаль не существует',
                    'not-unique-value'                         => 'Значение :code должно быть уникальным.',
                    'incorrect-family-for-variant'             => 'Семья должна быть такой же, как родительская семья.',
                    'parent-not-exist'                         => 'Родителя не существует.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Категории',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Вы не можете удалить корневую категорию, связанную с каналом.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Каналы',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Канал с кодом :code не найден для удаления.',
                    'locale-not-found'         => 'Один или несколько языков не существуют.',
                    'root-category-not-found'  => 'Корневая категория не существует.',
                    'currency-not-found'       => 'Одна или несколько валют не существуют.',
                    'invalid-locale'           => 'Язык не существует.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'fields' => [
            'file-format'         => 'Формат файла',
            'with-media'          => 'С медиафайлами',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Статус',
            'enable'         => 'Включён',
            'all'            => 'Все',
        ],
        'products' => [
            'title'              => 'Продукты',
            'invalid-locales'    => 'Не все выбранные локали доступны для выбранных каналов.',
            'invalid-currencies' => 'Не все выбранные валюты доступны для выбранных каналов.',
            'filters'            => [
                'channels'             => 'Каналы',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Валюты',
                'currencies-info'      => 'Атрибуты цен экспортируются для каждой выбранной валюты. Оставьте пустым, чтобы экспортировать все валюты канала.',
                'locales'              => 'Локали',
                'locales-info'         => 'Локализуемые атрибуты экспортируются один раз для каждой выбранной локали. Оставьте пустым, чтобы экспортировать все локали канала.',
                'attributes'           => 'Атрибуты',
                'attributes-info'      => 'Экспортируются только выбранные атрибуты. Оставьте пустым, чтобы экспортировать все атрибуты семейства.',
                'attribute-families'   => 'Семейства атрибутов',
                'categories'           => 'Категории',
                'completeness'         => 'Полнота',
                'completeness-options' => [
                    'none'         => 'Без условия полноты',
                    'at-least-one' => 'Полный хотя бы в одной выбранной локали',
                    'all'          => 'Полный во всех выбранных локалях',
                ],
                'time-condition' => 'Условие по времени',
                'time-options'   => [
                    'none'              => 'Без условия по дате',
                    'last-n-days'       => 'Товары, обновлённые за последние N дней',
                    'between-dates'     => 'Товары, обновлённые между двумя датами',
                    'since-last-export' => 'Товары, обновлённые с момента последнего экспорта',
                ],
                'time-value'     => 'Количество дней',
                'time-date'      => 'Дата начала',
                'time-date-end'  => 'Дата окончания',
                'status'         => 'Статус',
                'status-options' => [
                    'enable'  => 'Включён',
                    'disable' => 'Отключён',
                    'all'     => 'Все',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Идентификаторы',
                'identifiers-info' => 'Вставьте по одному SKU / идентификатору в строке, чтобы экспортировать только эти товары. Оставьте пустым, чтобы экспортировать все товары.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Ключ URL: \'%s\' уже был создан для элемента с SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Недопустимое значение для столбца семейства атрибутов (семейство атрибутов не существует?)',
                    'invalid-type'              => 'Тип продукта недействителен или не поддерживается.',
                    'sku-not-found'             => 'Товар с указанным артикулом не найден',
                    'super-attribute-not-found' => 'Суператрибут с кодом: \'%s\' не найден или не принадлежит к семейству атрибутов: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Категории',
        ],
        'channels' => [
            'title' => 'Каналы',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Статус',
                'active' => 'Active',
                'all'    => 'Все',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Столбцы с номером «%s» имеют пустые заголовки.',
            'column-name-invalid'  => 'Недопустимые имена столбцов: «%s».',
            'column-not-found'     => 'Обязательные столбцы не найдены: %s.',
            'column-numbers'       => 'Количество столбцов не соответствует количеству строк в заголовке.',
            'invalid-attribute'    => 'Заголовок содержит недопустимые атрибуты: "%s".',
            'system'               => 'Произошла непредвиденная системная ошибка.',
            'wrong-quotes'         => 'Вместо прямых кавычек используются фигурные кавычки.',
            'file-empty'           => 'Файл пуст или не содержит строку заголовка. Пожалуйста, загрузите корректный файл с данными.',
        ],
    ],
    'job' => [
        'started'   => 'Выполнение задания началось',
        'completed' => 'Выполнение задания завершено',
    ],
];
