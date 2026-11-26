<?php

return [
    'acl' => [
        'variant-structures' => 'Структуры вариантов',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'копия - :value',
        'copy-of'                       => 'копия :value',
        'variant-already-exist-message' => 'Вариант с такими же параметрами атрибутов уже существует.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Продукты типа :type не могут быть скопированы',
    ],

    'type'     => [
        'configurable'  => 'Настраиваемый',
        'simple'        => 'Простой',
        'variant-group' => 'Группа вариантов',
        'variant_group' => 'Группа вариантов',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Обнаружена циклическая ссылка в шаблоне: :path.',
            'currency-not-allowed'    => 'Валюту принимают только атрибуты цены, но ":attribute" не является атрибутом цены.',
            'currency-not-enabled'    => 'Валюта ":currency", используемая в шаблоне, не включена ни для одного канала.',
            'currency-not-in-channel' => 'Валюта ":currency" не включена для канала ":channel".',
            'filter-failed'           => 'Не удалось применить фильтр ":filter" к значению ":attribute".',
            'filter-not-supported'    => 'Фильтр ":filter" нельзя применить к атрибуту ":attribute".',
            'format-date-position'    => 'Фильтр format_date должен быть первым фильтром в ":expression".',
            'inside-html-tag'         => 'Выражения шаблона нельзя размещать внутри HTML-тегов или атрибутов, например ссылок и изображений.',
            'invalid-filter-argument' => 'У фильтра ":filter" в ":expression" отсутствует аргумент или он недопустим.',
            'invalid-reference'       => 'Шаблон, сохранённый в ":attribute", недействителен, и его не удалось отобразить.',
            'invalid-syntax'          => 'Выражение шаблона ":expression" недействительно. Используйте код атрибута, за которым следуют необязательные фильтры, например: name | upper.',
            'max-depth'               => 'Ссылки шаблонов в ":attribute" имеют вложенность более :max уровней.',
            'missing-value'           => 'У атрибута ":attribute" нет значения для этого канала и локали.',
            'not-in-family'           => 'Атрибут ":attribute", используемый в шаблоне, не входит в семейство продукта.',
            'output-too-large'        => 'Отрисованный шаблон превышает :max байт и был обрезан.',
            'self-reference'          => 'Шаблон не может ссылаться на собственный атрибут ":attribute".',
            'single-brace'            => 'Найдено ":text" в одинарных фигурных скобках. Выражения шаблона требуют двойных фигурных скобок.',
            'source-too-long'         => 'Длина шаблона не может превышать :max байт.',
            'too-complex'             => 'Шаблон слишком сложен для проверки. Сократите или упростите его.',
            'too-many-expressions'    => 'Шаблон может содержать не более :max выражений.',
            'too-many-filters'        => 'Выражение шаблона ":expression" может использовать не более :max фильтров.',
            'unclosed-expression'     => 'Выражение шаблона было открыто, но так и не закрыто.',
            'unknown-attribute'       => 'Атрибут ":attribute", используемый в шаблоне, не существует.',
            'unknown-currency'        => 'Валюта ":currency", используемая в шаблоне, не существует.',
            'unknown-filter'          => 'Фильтр ":filter" не поддерживается. Доступные фильтры: :filters.',
            'unsupported-attribute'   => 'Атрибут ":attribute" нельзя использовать в шаблоне, так как его тип не поддерживается.',
        ],
    ],
];
