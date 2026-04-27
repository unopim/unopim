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
        'attributes' => [
            'title'      => 'Атрибуты',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Код атрибута :code уже используется.',
                    'code_not_found_to_delete'             => 'Код атрибута для удаления не найден.',
                    'code_is_system_and_cannot_be_deleted' => 'Системный атрибут не может быть удален.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Группы атрибутов',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Код группы атрибутов :code уже используется.',
                    'code_not_found_to_delete'             => 'Код группы атрибутов для удаления не найден.',
                    'code_is_system_and_cannot_be_deleted' => 'Системная группа атрибутов не может быть удалена.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Семейства атрибутов',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Код семейства атрибутов :code уже используется.',
                    'code_not_found_to_delete' => 'Код семейства атрибутов для удаления не найден.',
                    'invalid-attribute-group'  => 'Группа атрибутов ":code" не существует.',
                    'invalid-attribute'        => 'Атрибут ":code" не существует.',
                    'invalid-channel'          => 'Канал ":code" не существует.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Параметры атрибутов',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Код параметра атрибута :code уже используется.',
                    'code_not_found_to_delete' => 'Код параметра атрибута для удаления не найден.',
                    'locale-not-exist'         => 'Локаль ":code" не существует.',
                    'invalid-attribute'        => 'Атрибут ":code" не существует.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Продукты',
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
        'attributes' => [
            'title' => 'Атрибуты',
        ],
        'attribute-groups' => [
            'title' => 'Группы атрибутов',
        ],
        'attribute-families' => [
            'title' => 'Семейства атрибутов',
        ],
        'attribute-options' => [
            'title' => 'Параметры атрибутов',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Выполнение задания началось',
        'completed' => 'Выполнение задания завершено',
    ],
];
