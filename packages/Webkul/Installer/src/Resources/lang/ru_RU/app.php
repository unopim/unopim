<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'По умолчанию',
            ],

            'attribute-groups' => [
                'description'      => 'Описание',
                'general'          => 'Общий',
                'meta-description' => 'Мета-описание',
                'price'            => 'Цена',
                'media'            => 'Медиа',
            ],

            'attributes' => [
                'brand'                => 'Бренд',
                'color'                => 'Цвет',
                'cost'                 => 'Расходы',
                'description'          => 'Описание',
                'featured'             => 'Рекомендуемые',
                'guest-checkout'       => 'Гостевая касса',
                'height'               => 'Высота',
                'image'                => 'Изображение',
                'length'               => 'Длина',
                'manage-stock'         => 'Управление запасами',
                'meta-description'     => 'Мета-описание',
                'meta-keywords'        => 'Мета-ключевые слова',
                'meta-title'           => 'Мета-заголовок',
                'name'                 => 'Имя',
                'new'                  => 'Новый',
                'price'                => 'Цена',
                'product-number'       => 'Номер продукта',
                'short-description'    => 'Краткое описание',
                'size'                 => 'Размер',
                'sku'                  => 'Артикул',
                'special-price-from'   => 'Специальная цена от',
                'special-price-to'     => 'Специальная цена',
                'special-price'        => 'Специальная цена',
                'tax-category'         => 'Налоговая категория',
                'url-key'              => 'URL-ключ',
                'visible-individually' => 'Виден индивидуально',
                'weight'               => 'Масса',
                'width'                => 'Ширина',
            ],

            'attribute-options' => [
                'black'  => 'Черный',
                'green'  => 'Зеленый',
                'l'      => 'л',
                'm'      => 'М',
                'red'    => 'Красный',
                's'      => 'С',
                'white'  => 'Белый',
                'xl'     => 'XL',
                'yellow' => 'Желтый',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'Описание корневой категории',
                'name'        => 'Корень',
            ],

            'category_fields' => [
                'name'        => 'Имя',
                'description' => 'Описание',
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'Демо-магазин',
                'meta-keywords'    => 'Мета-ключевое слово демо-магазина',
                'meta-description' => 'Мета-описание демо-магазина',
                'name'             => 'По умолчанию',
            ],

            'currencies' => [
                'AED' => 'Дирхам',
                'AFN' => 'Израильский шекель',
                'CNY' => 'Китайский юань',
                'EUR' => 'ЕВРО',
                'GBP' => 'Фунт стерлингов',
                'INR' => 'Индийская рупия',
                'IRR' => 'Иранский риал',
                'JPY' => 'Японская иена',
                'RUB' => 'Российский рубль',
                'SAR' => 'Саудовский риал',
                'TRY' => 'Турецкая лира',
                'UAH' => 'Украинская гривна',
                'USD' => 'Доллар США',
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'Пользователи с этой ролью будут иметь весь доступ',
                'name'        => 'Администратор',
            ],

            'users' => [
                'name' => 'Пример',
            ],
        ],
    ],

    'installer' => [

        'middleware' => [
            'already-installed' => 'Приложение уже установлено.',
        ],

        'index' => [
            'create-administrator' => [
                'admin'            => 'Админ',
                'unopim'           => 'УноПим',
                'confirm-password' => 'Подтвердите пароль',
                'email-address'    => 'admin@example.com',
                'email'            => 'Электронная почта',
                'password'         => 'Пароль',
                'title'            => 'Создать администратора',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Разрешенные валюты',
                'allowed-locales'     => 'Разрешенные локали',
                'application-name'    => 'Имя приложения',
                'unopim'              => 'УноПим',
                'chinese-yuan'        => 'Китайский юань (CNY)',
                'database-connection' => 'Подключение к базе данных',
                'database-hostname'   => 'Имя хоста базы данных',
                'database-name'       => 'Имя базы данных',
                'database-password'   => 'Пароль базы данных',
                'database-port'       => 'Порт базы данных',
                'database-prefix'     => 'Префикс базы данных',
                'database-username'   => 'Имя пользователя базы данных',
                'default-currency'    => 'Валюта по умолчанию',
                'default-locale'      => 'Язык по умолчанию',
                'default-timezone'    => 'Часовой пояс по умолчанию',
                'default-url-link'    => 'https://локальный хост',
                'default-url'         => 'URL-адрес по умолчанию',
                'dirham'              => 'Дирхам (AED)',
                'euro'                => 'Евро (EUR)',
                'iranian'             => 'Иранский риал (IRR)',
                'israeli'             => 'Израильский шекель (AFN)',
                'japanese-yen'        => 'Японская иена (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Фунт стерлингов (GBP)',
                'rupee'               => 'Индийская рупия (INR)',
                'russian-ruble'       => 'Российский рубль (RUB)',
                'saudi'               => 'Саудовский риал (SAR)',
                'select-timezone'     => 'Выберите часовой пояс',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Конфигурация базы данных',
                'turkish-lira'        => 'Турецкая лира (TRY)',
                'ukrainian-hryvnia'   => 'Украинская гривна (UAH)',
                'usd'                 => 'Доллар США (USD)',
                'warning-message'     => 'Остерегаться! Настройки системных языков по умолчанию, а также валюты по умолчанию являются постоянными и не могут быть изменены больше никогда.',
            ],

            'installation-processing' => [
                'unopim'      => 'Установка УноПим',
                'unopim-info' => 'Создание таблиц базы данных. Это может занять несколько минут.',
                'title'       => 'Установка',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Панель администратора',
                'unopim-forums'             => 'Форум UnoPim',
                'explore-unopim-extensions' => 'Изучите расширение UnoPim',
                'title-info'                => 'UnoPim успешно установлен в вашей системе.',
                'title'                     => 'Установка завершена',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Создайте таблицу базы данных',
                'install-info-button'     => 'Нажмите кнопку ниже, чтобы',
                'install-info'            => 'UnoPim для установки',
                'install'                 => 'Установка',
                'populate-database-table' => 'Заполните таблицы базы данных',
                'start-installation'      => 'Начать установку',
                'title'                   => 'Готов к установке',
            ],

            'start' => [
                'locale'        => 'Языковой стандарт',
                'main'          => 'Начинать',
                'select-locale' => 'Выберите локаль',
                'title'         => 'Ваша установка UnoPim',
                'welcome-title' => 'Добро пожаловать в УноПим :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Календарь',
                'ctype'       => 'cType',
                'curl'        => 'КУЛЬ',
                'dom'         => 'дом',
                'fileinfo'    => 'информация о файле',
                'filter'      => 'Фильтр',
                'gd'          => 'ГД',
                'hash'        => 'Хэш',
                'intl'        => 'международный',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'pcre',
                'pdo'         => 'пдо',
                'php-version' => '8.2 или выше',
                'php'         => 'PHP',
                'session'     => 'сессия',
                'title'       => 'Системные требования',
                'tokenizer'   => 'токенизатор',
                'xml'         => 'XML',
            ],

            'back'                     => 'Назад',
            'unopim-info'              => 'общественный проект от',
            'unopim-logo'              => 'Логотип УноПим',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Продолжать',
            'installation-description' => 'Установка UnoPim обычно включает в себя несколько шагов. Вот общая схема процесса установки UnoPim:',
            'wizard-language'          => 'Язык мастера установки',
            'installation-info'        => 'Мы рады видеть вас здесь!',
            'installation-title'       => 'Добро пожаловать в установку',
            'save-configuration'       => 'Сохранить конфигурацию',
            'skip'                     => 'Пропускать',
            'title'                    => 'УноПим Установщик',
            'webkul'                   => 'Webkul',
        ],
    ],
];
