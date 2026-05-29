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
        'category-fields' => [
            'title'      => 'Поля категорії',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Код поля категорії :code вже використовується.',
                    'code_not_found_to_delete' => 'Код поля категорії не знайдено для видалення.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Атрибути',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Код атрибута :code вже використовується.',
                    'code_not_found_to_delete'             => 'Код атрибута для видалення не знайдено.',
                    'code_is_system_and_cannot_be_deleted' => 'Системний атрибут не може бути видалено.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Групи атрибутів',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Код групи атрибутів :code вже використовується.',
                    'code_not_found_to_delete'             => 'Код групи атрибутов для видалення не знайдено.',
                    'code_is_system_and_cannot_be_deleted' => 'Системна група атрибутів не може бути видалена.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Сімейства атрибутів',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Код сімейства атрибутів :code вже використовується.',
                    'code_not_found_to_delete' => 'Код сімейства атрибутів для видалення не знайдено.',
                    'invalid-attribute-group'  => 'Група атрибутів ":code" не існує.',
                    'invalid-attribute'        => 'Атрибут ":code" не існує.',
                    'invalid-channel'          => 'Канал ":code" не існує.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Варіанти атрибутів',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Код варіанта атрибута :code вже використовується.',
                    'code_not_found_to_delete' => 'Код варіанта атрибута для видалення не знайдено.',
                    'locale-not-exist'         => 'Локаль ":code" не існує.',
                    'invalid-attribute'        => 'Атрибут ":code" не існує.',
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
        'category-fields' => [
            'title' => 'Поля категорії',
        ],
        'attributes' => [
            'title' => 'Атрибути',
        ],
        'attribute-groups' => [
            'title' => 'Групи атрибутів',
        ],
        'attribute-families' => [
            'title' => 'Сімейства атрибутів',
        ],
        'attribute-options' => [
            'title' => 'Варіанти атрибутів',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Запущено виконання завдання',
        'completed' => 'Завдання виконане',
    ],
];
