<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Продукти',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL ключ: \'%s\' вже створено для товару зі SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Недопустиме значення для стовпця сімейства атрибутів (сімейство атрибутів відсутнє?)',
                    'invalid-type'                             => 'Тип продукту недійсний або не підтримується',
                    'sku-not-found'                            => 'Продукт з вказаним SKU не знайдено',
                    'super-attribute-not-found'                => 'Конфігурований атрибут з кодом: \'%s\' не знайдено або не належить сімейству атрибутів: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Конфігуровані атрибути потрібні для створення моделі продукту',
                    'configurable-attributes-wrong-type'       => 'Тільки вибрані атрибути типу, які не базуються на місті або каналі, можуть бути конфігурованими атрибутами для конфігурованого продукту',
                    'variant-configurable-attribute-not-found' => 'Потрібний конфігурований атрибут варіанту: :code',
                    'not-unique-variant-product'               => 'Продукт з такими конфігурованими атрибутами вже існує.',
                    'channel-not-exist'                        => 'Цей канал не існує.',
                    'locale-not-in-channel'                    => 'Ця локалізація не вибрана в каналі.',
                    'locale-not-exist'                         => 'Ця локалізація не існує',
                    'not-unique-value'                         => 'Значення :code повинно бути унікальним.',
                    'incorrect-family-for-variant'             => 'Сімейство має бути таким самим, як і основне сімейство',
                    'parent-not-exist'                         => 'Батьківський товар не існує.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Категорії',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Ви не можете видалити кореневу категорію, яка пов\'язана з каналом',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Канали',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Канал з кодом :code не знайдено для видалення.',
                    'locale-not-found'         => 'Одна або кілька мов не існують.',
                    'root-category-not-found'  => 'Коренева категорія не існує.',
                    'currency-not-found'       => 'Одна або кілька валют не існують.',
                    'invalid-locale'           => 'Мова не існує.',
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
        'export-too-large' => 'Цей експорт надто великий для виконання: приблизно :rows рядків × :columns стовпців (~:estimated) перевищують доступний простір (~:available). Звузьте експорт, вибравши менше каналів/локалей (та атрибутів), і повторіть спробу.',
        'fields'           => [
            'file-format'         => 'Формат файлу',
            'with-media'          => 'З медіафайлами',
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
            'enable'         => 'Увімкнено',
            'all'            => 'Усі',
        ],
        'products' => [
            'title'              => 'Продукти',
            'invalid-locales'    => 'Не всі вибрані локалі доступні для вибраних каналів.',
            'invalid-currencies' => 'Не всі вибрані валюти доступні для вибраних каналів.',
            'filters'            => [
                'channels'             => 'Канали',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Валюти',
                'currencies-info'      => 'Атрибути цін експортуються для кожної вибраної валюти. Залиште порожнім, щоб експортувати всі валюти каналу.',
                'locales'              => 'Локалі',
                'locales-info'         => 'Локалізовані атрибути експортуються один раз для кожної вибраної локалі. Залиште порожнім, щоб експортувати всі локалі каналу.',
                'attributes'           => 'Атрибути',
                'attributes-info'      => 'Експортуються лише вибрані атрибути. Залиште порожнім, щоб експортувати всі атрибути сімейства.',
                'attribute-families'   => 'Сімейства атрибутів',
                'categories'           => 'Категорії',
                'completeness'         => 'Повнота',
                'completeness-options' => [
                    'none'         => 'Без умови повноти',
                    'at-least-one' => 'Повний хоча б в одній вибраній локалі',
                    'all'          => 'Повний у всіх вибраних локалях',
                ],
                'time-condition' => 'Умова за часом',
                'time-options'   => [
                    'none'              => 'Без умови за датою',
                    'last-n-days'       => 'Товари, оновлені за останні N днів',
                    'between-dates'     => 'Товари, оновлені між двома датами',
                    'since-last-export' => 'Товари, оновлені з моменту останнього експорту',
                ],
                'time-value'     => 'Кількість днів',
                'time-date'      => 'Дата початку',
                'time-date-end'  => 'Дата завершення',
                'status'         => 'Статус',
                'status-options' => [
                    'enable'  => 'Увімкнено',
                    'disable' => 'Вимкнено',
                    'all'     => 'Усі',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Ідентифікатори',
                'identifiers-info' => 'Вставте по одному SKU / ідентифікатору в рядку, щоб експортувати лише ці товари. Залиште порожнім, щоб експортувати всі товари.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL ключ: \'%s\' вже створено для товару зі SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Недопустиме значення для стовпця сімейства атрибутів (сімейство атрибутів відсутнє?)',
                    'invalid-type'              => 'Тип продукту недійсний або не підтримується',
                    'sku-not-found'             => 'Продукт з вказаним SKU не знайдено',
                    'super-attribute-not-found' => 'Конфігурований атрибут з кодом: \'%s\' не знайдено або не належить сімейству атрибутів: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Категорії',
        ],
        'channels' => [
            'title' => 'Канали',
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
                'all'    => 'Усі',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Кількість колонок "%s" має пусті заголовки.',
            'column-name-invalid'  => 'Недійсні імена колонок: "%s".',
            'column-not-found'     => 'Вимагані колонки не знайдені: %s.',
            'column-numbers'       => 'Кількість колонок не відповідає кількості рядків у заголовку.',
            'invalid-attribute'    => 'Заголовок містить недійсні атрибути: "%s".',
            'system'               => 'Сталася несподівана система помилка.',
            'wrong-quotes'         => 'Використано криві лапки замість прямих лапок.',
            'file-empty'           => 'Файл порожній або не містить рядка заголовка. Будь ласка, завантажте коректний файл з даними.',
        ],
    ],
    'job' => [
        'started'   => 'Запущено виконання завдання',
        'completed' => 'Завдання виконане',
    ],
];
