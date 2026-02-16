<?php

return [
    'exporters' => [
        'shopify' => [
            'product'  => 'Shopify продукт',
            'category' => 'Shopify категория',
        ],
    ],
    'importers' => [
        'shopify' => [
            'product'  => 'Продукт Shopify',
            'category' => 'Категория Shopify',
            'attribute'=> 'Атрибут Shopify',
            'family'   => 'Семья Shopify',
            'metafield'=> 'Определения метаполей Shopify',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'shopify'         => 'Shopify',
                'credentials'     => 'Учетные данные',
                'export-mappings' => 'Экспортные сопоставления',
                'import-mappings' => 'Импорт маппингов',
                'settings'        => 'Настройки',
            ],
        ],
    ],

    'shopify' => [
        'acl' => [
            'credential' => [
                'create' => 'Создать',
                'edit'   => 'Редактировать',
                'delete' => 'Удалить',
            ],

            'metafield' => [
                'create'      => 'Создать метаполе',
                'edit'        => 'Редактировать метаполе',
                'delete'      => 'Удалить метаполе',
                'mass_delete' => 'Массовое удаление метаполей',
            ],
        ],

        'version' => 'Версия: 1.0.0',

        'credential' => [
            'export' => [
                'locales' => 'Сопоставление локалей',
            ],
            'shopify' => [
                'locale' => 'Shopify локаль',
            ],
            'unopim' => [
                'locale' => 'Unopim локаль',
            ],
            'delete-success' => 'Учетные данные успешно удалены',
            'created'        => 'Учетные данные успешно созданы',
            'update-success' => 'Успешно обновлено',
            'invalid'        => 'Неверные учетные данные',
            'invalidurl'     => 'Неверный URL',
            'already_taken'  => 'URL магазина уже занят.',
            'index'          => [
                'title'                 => 'Учетные данные Shopify',
                'create'                => 'Создать учетные данные',
                'url'                   => 'Shopify URL',
                'shopifyurlplaceholder' => 'Shopify URL (например, http://demo.myshopify.com)',
                'accesstoken'           => 'Токен доступа Admin API',
                'apiVersion'            => 'Версия API',
                'save'                  => 'Сохранить',
                'back-btn'              => 'Назад',
                'channel'               => 'Публикация (каналы продаж)',
                'locations'             => 'Список местоположений',
            ],
            'edit' => [
                'title'    => 'Редактировать учетные данные',
                'delete'   => 'Удалить учетные данные',
                'back-btn' => 'Назад',
                'update'   => 'Обновить',
                'save'     => 'Сохранить',
            ],
            'datagrid' => [
                'shopUrl'    => 'Shopify URL',
                'apiVersion' => 'Версия API',
                'enabled'    => 'Включено',

            ],
        ],
        'export' => [
            'mapping' => [
                'title'         => 'Экспортные сопоставления',
                'back-btn'      => 'Назад',
                'save'          => 'Сохранить',
                'created'       => 'Экспортное сопоставление создано',
                'image'         => 'Атрибут для использования в качестве изображения',
                'metafields'    => 'Атрибуты для использования в метаполях',
                'filed-shopify' => 'Поле в Shopify',
                'attribute'     => 'Атрибут',
                'fixed-value'   => 'Фиксированное значение',
            ],
            'setting' => [
                'title'                        => 'Настройка',
                'tags'                         => 'Настройка экспорта тегов',
                'enable_metric_tags_attribute' => 'Хотите ли вы добавлять название метрической единицы в теги?',
                'enable_named_tags_attribute'  => 'Хотите ли вы использовать именованные теги?',
                'tagSeprator'                  => 'Использовать разделитель имени атрибута в тегах',
                'enable_tags_attribute'        => 'Хотите ли вы добавлять название атрибута в теги?',
                'metafields'                   => 'Настройка экспорта метаполей',
                'metaFieldsKey'                => 'Использовать ключ для метаполя как код/метку атрибута',
                'metaFieldsNameSpace'          => 'Использовать пространство имен для метаполя как код группы атрибутов/глобальный',
                'other-settings'               => 'Прочие настройки',
                'roundof-attribute-value'      => 'Удалить лишние дробные нули в значении метрического атрибута (например, 201.2000 как 201.2)',
                'option_name_label'            => 'Значение для имени опции как метка атрибута (по умолчанию код атрибута)',
            ],

            'errors' => [
                'invalid-credential' => 'Неверные учетные данные. Учетные данные отключены или неверны.',
                'invalid-locale'     => 'Неверный локаль. Пожалуйста, настройте локаль в разделе редактирования учетных данных.',
            ],
        ],
        'import' => [
            'mapping' => [
                'title'                => 'Маппинг импорта',
                'back-btn'             => 'Назад',
                'save'                 => 'Сохранить',
                'created'              => 'Маппинг импорта успешно сохранен',
                'image'                => 'Атрибут, используемый как изображение',
                'filed-shopify'        => 'Поле в Shopify',
                'attribute'            => 'Атрибут UnoPim',
                'variantimage'         => 'Атрибут, используемый как изображение вариации',
                'other'                => 'Другие маппинги Shopify',
                'family'               => 'Маппинг семейства (для продуктов)',
                'metafieldDefinitions' => 'Маппинг определения метаполя Shopify',
            ],
            'setting' => [
                'credentialmapping' => 'Маппинг учетных данных',
            ],
            'job' => [
                'product' => [
                    'family-not-exist'      => 'Семейство не существует для заголовка: - :title. Сначала нужно импортировать семейство',
                    'variant-sku-not-exist' => 'SKU вариации не найдено в продукте: - :id',
                    'duplicate-sku'         => ':sku : - Найдено дублирующееся SKU в продукте',
                    'required-field'        => ':attribute : - Поле обязательно для SKU: - :sku',
                    'family-not-mapping'    => 'Семейство не сопоставлено для заголовка: - :title',
                    'attribute-not-exist'   => 'Атрибуты :attributes не существуют для продукта',
                    'not-found-sku'         => 'SKU не найдено в продукте: - :id',
                    'option-not-found'      => ':attribute - :option Опция не найдена в SKU UnoPim: - :sku',
                ],
            ],
        ],

        'fields' => [
            'name'                        => 'Название',
            'description'                 => 'Описание',
            'price'                       => 'Цена',
            'weight'                      => 'Вес',
            'quantity'                    => 'Количество',
            'inventory_tracked'           => 'Отслеживание запасов',
            'allow_purchase_out_of_stock' => 'Разрешить покупку при отсутствии на складе',
            'vendor'                      => 'Поставщик',
            'product_type'                => 'Тип продукта',
            'tags'                        => 'Теги',
            'barcode'                     => 'Штрих-код',
            'compare_at_price'            => 'Цена для сравнения',
            'seo_title'                   => 'SEO заголовок',
            'seo_description'             => 'SEO описание',
            'handle'                      => 'Идентификатор',
            'taxable'                     => 'Облагается налогом',
            'inventory_cost'              => 'Стоимость инвентаря',
        ],
        'exportmapping' => 'Сопоставления атрибутов',
        'job'           => [
            'credentials'      => 'Учетные данные Shopify',
            'channel'          => 'Канал',
            'currency'         => 'Валюта',
            'productfilter'    => 'Фильтр продуктов (SKU)',
            'locale'           => 'Языковая локаль',
            'attribute-groups' => 'Группы атрибутов',
        ],
    ],
];
