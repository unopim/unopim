<?php

return [
    'acl' => [
        'variant-structures' => 'Структури варіантів',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'копія-:value',
        'copy-of'                       => ':value копія',
        'variant-already-exist-message' => 'Варіант із такими самими опціями атрибутів вже існує.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Товари типу :type не можна скопіювати',
    ],

    'type'     => [
        'configurable'  => 'Конфігурований',
        'simple'        => 'Простий',
        'variant-group' => 'Група варіантів',
        'variant_group' => 'Група варіантів',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Виявлено циклічне посилання в шаблоні: :path.',
            'currency-not-allowed'    => 'Валюту приймають лише атрибути ціни, але ":attribute" не є атрибутом ціни.',
            'currency-not-enabled'    => 'Валюту ":currency", використану в шаблоні, не ввімкнено для жодного каналу.',
            'currency-not-in-channel' => 'Валюту ":currency" не ввімкнено для каналу ":channel".',
            'filter-failed'           => 'Не вдалося застосувати фільтр ":filter" до значення ":attribute".',
            'filter-not-supported'    => 'Фільтр ":filter" не можна застосувати до атрибута ":attribute".',
            'format-date-position'    => 'Фільтр format_date має бути першим фільтром у ":expression".',
            'inside-html-tag'         => 'Вирази шаблону не можна розміщувати всередині HTML-тегів або атрибутів, наприклад посилань і зображень.',
            'invalid-filter-argument' => 'Фільтр ":filter" у ":expression" має відсутній або недійсний аргумент.',
            'invalid-reference'       => 'Шаблон, збережений у ":attribute", недійсний, і його не вдалося відобразити.',
            'invalid-syntax'          => 'Вираз шаблону ":expression" недійсний. Використовуйте код атрибута, за яким ідуть необов\'язкові фільтри, наприклад: name | upper.',
            'max-depth'               => 'Посилання шаблонів у ":attribute" мають вкладеність понад :max рівнів.',
            'missing-value'           => 'Атрибут ":attribute" не має значення для цього каналу та мови.',
            'not-in-family'           => 'Атрибут ":attribute", використаний у шаблоні, не належить до сімейства продукту.',
            'output-too-large'        => 'Відрендерений шаблон перевищує :max байтів і був обрізаний.',
            'self-reference'          => 'Шаблон не може посилатися на власний атрибут ":attribute".',
            'single-brace'            => 'Знайдено ":text" в одинарних фігурних дужках. Вирази шаблону потребують подвійних фігурних дужок.',
            'source-too-long'         => 'Довжина шаблону не може перевищувати :max байтів.',
            'too-complex'             => 'Шаблон занадто складний для перевірки. Скоротіть або спростіть його.',
            'too-many-expressions'    => 'Шаблон може містити не більше :max виразів.',
            'too-many-filters'        => 'Вираз шаблону ":expression" може використовувати не більше :max фільтрів.',
            'unclosed-expression'     => 'Вираз шаблону було відкрито, але так і не закрито.',
            'unknown-attribute'       => 'Атрибут ":attribute", використаний у шаблоні, не існує.',
            'unknown-currency'        => 'Валюта ":currency", використана в шаблоні, не існує.',
            'unknown-filter'          => 'Фільтр ":filter" не підтримується. Доступні фільтри: :filters.',
            'unsupported-attribute'   => 'Атрибут ":attribute" не можна використовувати в шаблоні, оскільки його тип не підтримується.',
        ],
    ],
];
