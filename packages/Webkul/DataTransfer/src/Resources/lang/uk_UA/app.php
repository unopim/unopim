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
        'locales' => [
            'title'      => 'Мови',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Код мови \'%s\' вже імпортовано в цьому пакеті.',
                    'code-not-found-to-delete'    => 'Мову з кодом \'%s\' не знайдено в системі.',
                    'invalid-status'              => 'Статус має бути 0 або 1 (або порожній для увімкнення за замовчуванням).',
                    'channel-related-locale-root' => 'Ви не можете видалити мову з кодом :code, оскільки вона пов’язана з каналом.',
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
        'products' => [
            'title'      => 'Продукти',
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
        'locales' => [
            'title' => 'Мови',
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
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
