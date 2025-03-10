<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => 'Сім’ї атрибутів',
            'attribute-groups'   => [
                'description'      => 'Опис',
                'general'          => 'Загальні',
                'inventories'      => 'Запаси',
                'meta-description' => 'Мета Опис',
                'price'            => 'Ціна',
                'technical'        => 'Технічні',
                'shipping'         => 'Доставка',
            ],
            'attributes' => [
                'brand'                => 'Бренд',
                'color'                => 'Колір',
                'cost'                 => 'Вартість',
                'description'          => 'Опис',
                'featured'             => 'Рекомендовані',
                'guest-checkout'       => 'Оформлення замовлення для гостей',
                'height'               => 'Висота',
                'length'               => 'Довжина',
                'manage-stock'         => 'Управління запасами',
                'meta-description'     => 'Мета Опис',
                'meta-keywords'        => 'Мета Ключові слова',
                'meta-title'           => 'Мета Заголовок',
                'name'                 => 'Назва',
                'new'                  => 'Новий',
                'price'                => 'Ціна',
                'product-number'       => 'Номер товару',
                'short-description'    => 'Короткий опис',
                'size'                 => 'Розмір',
                'sku'                  => 'Артикул',
                'special-price-from'   => 'Спеціальна ціна з',
                'special-price-to'     => 'Спеціальна ціна до',
                'special-price'        => 'Спеціальна ціна',
                'status'               => 'Статус',
                'tax-category'         => 'Категорія податку',
                'url-key'              => 'URL Ключ',
                'visible-individually' => 'Видиме окремо',
                'weight'               => 'Вага',
                'width'                => 'Ширина',
            ],
            'attribute-options' => [
                'black'  => 'Чорний',
                'green'  => 'Зелений',
                'l'      => 'L',
                'm'      => 'M',
                'red'    => 'Червоний',
                's'      => 'S',
                'white'  => 'Білий',
                'xl'     => 'XL',
                'yellow' => 'Жовтий',
            ],
        ],
        'category' => [
            'categories' => [
                'description' => 'Опис категорій',
                'name'        => 'Головна',
            ],
            'category_fields' => [
                'name'        => 'Назва',
                'description' => 'Опис',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Зміст сторінки «Про нас»',
                    'title'   => 'Про нас',
                ],
                'contact-us' => [
                    'content' => 'Зміст сторінки «Зв’язатися з нами»',
                    'title'   => 'Зв’язатися з нами',
                ],
                'customer-service' => [
                    'content' => 'Зміст сторінки «Служба підтримки клієнтів»',
                    'title'   => 'Служба підтримки клієнтів',
                ],
                'payment-policy' => [
                    'content' => 'Зміст сторінки «Оплата»',
                    'title'   => 'Оплата',
                ],
                'privacy-policy' => [
                    'content' => 'Зміст сторінки «Політика конфіденційності»',
                    'title'   => 'Політика конфіденційності',
                ],
                'refund-policy' => [
                    'content' => 'Зміст сторінки «Політика повернення»',
                    'title'   => 'Політика повернення',
                ],
                'return-policy' => [
                    'content' => 'Зміст сторінки «Політика повернення товару»',
                    'title'   => 'Політика повернення товару',
                ],
                'shipping-policy' => [
                    'content' => 'Зміст сторінки «Політика доставки»',
                    'title'   => 'Політика доставки',
                ],
                'terms-conditions' => [
                    'content' => 'Зміст сторінки «Умови»',
                    'title'   => 'Умови',
                ],
                'terms-of-use' => [
                    'content' => 'Зміст сторінки «Умови використання»',
                    'title'   => 'Умови використання',
                ],
                'whats-new' => [
                    'content' => 'Зміст сторінки «Що нового»',
                    'title'   => 'Що нового',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => 'Демо Магазин',
                'meta-keywords'    => 'Мета Ключові слова Демо Магазину',
                'meta-description' => 'Мета Опис Демо Магазину',
                'name'             => 'Стандарт',
            ],
            'currencies' => [
                'AED' => 'Дірхам',
                'AFN' => 'Афгані',
                'CNY' => 'Китайський юань',
                'EUR' => 'Євро',
                'GBP' => 'Фунт стерлінгів',
                'INR' => 'Індійська рупія',
                'IRR' => 'Іранський ріал',
                'JPY' => 'Японська єна',
                'RUB' => 'Російський рубль',
                'SAR' => 'Саудівський ріал',
                'TRY' => 'Турецька ліра',
                'UAH' => 'Українська гривня',
                'USD' => 'Долар США',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => 'Загальний',
                'guest'     => 'Гість',
                'wholesale' => 'Оптова торгівля',
            ],
        ],
        'inventory' => [
            'inventory-sources' => [
                'name' => 'Стандарт',
            ],
        ],
        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name'    => 'Усі продукти',
                    'options' => [
                        'title' => 'Усі продукти',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Переглянути все',
                        'description' => 'Досліджуйте наші нові сміливі колекції! Підніміть свій стиль, додаючи яскраві кольори і сміливі дизайни. Додайте різноманітності у ваш гардероб з нашими сміливими принтами і насиченими кольорами. Готуйтеся до сміливого початку!',
                        'title'       => 'Готуйтеся до наших нових сміливих колекцій!',
                    ],
                    'name' => 'Сміливі колекції',
                ],
                'categories-collections' => [
                    'name' => 'Колекції за категоріями',
                ],
                'featured-collections' => [
                    'name'    => 'Рекомендовані колекції',
                    'options' => [
                        'title' => 'Рекомендовані продукти',
                    ],
                ],
                'footer-links' => [
                    'name'    => 'Посилання внизу',
                    'options' => [
                        'about-us'         => 'Про нас',
                        'contact-us'       => 'Зв’язатися з нами',
                        'customer-service' => 'Служба підтримки клієнтів',
                        'payment-policy'   => 'Оплата',
                        'privacy-policy'   => 'Політика конфіденційності',
                        'refund-policy'    => 'Політика повернення',
                        'return-policy'    => 'Політика повернення товару',
                        'shipping-policy'  => 'Політика доставки',
                        'terms-conditions' => 'Умови',
                        'terms-of-use'     => 'Умови використання',
                        'whats-new'        => 'Що нового',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Наші колекції',
                        'sub-title-2' => 'Наші колекції',
                        'title'       => 'Грайте з новими продуктами!',
                    ],
                    'name' => 'Гральний контейнер',
                ],
                'image-carousel' => [
                    'name'    => 'Карусель зображень',
                    'sliders' => [
                        'title' => 'Підготуйтеся до нової колекції',
                    ],
                ],
                'new-products' => [
                    'name'    => 'Нові продукти',
                    'options' => [
                        'title' => 'Нові продукти',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'ПОЧНИТЕ НАВІГАЦІЮ З %40 ЗНИЖКОЮ НА ПЕРШУ ПОКУПКУ!',
                    ],
                    'name' => 'Інформація про пропозицію',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'Фінансування без додаткової плати для всіх основних кредитних карт',
                        'free-shipping-info'   => 'Безкоштовна доставка для всіх замовлень',
                        'product-replace-info' => 'Заміна продукту здійснюється легко!',
                        'time-support-info'    => 'Спеціальна підтримка 24/7 через живий чат і електронну пошту',
                    ],
                    'name'  => 'Зміст послуг',
                    'title' => [
                        'emi-available'   => 'Фінансування доступне',
                        'free-shipping'   => 'Безкоштовна доставка',
                        'product-replace' => 'Заміна продукту',
                        'time-support'    => 'Підтримка 24/7',
                    ],
                ],
                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Наші колекції',
                        'sub-title-2' => 'Наші колекції',
                        'sub-title-3' => 'Наші колекції',
                        'sub-title-4' => 'Наші колекції',
                        'sub-title-5' => 'Наші колекції',
                        'sub-title-6' => 'Наші колекції',
                        'title'       => 'Грайте з новими продуктами!',
                    ],
                    'name' => 'Рекомендовані колекції',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'Ця роль матиме доступ до всіх можливостей',
                'name'        => 'Адміністратор',
            ],
            'users' => [
                'name' => 'Приклад',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Адміністратор',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Підтвердити пароль',
                'email-address'    => 'admin@example.com',
                'email'            => 'Електронна пошта',
                'password'         => 'Пароль',
                'title'            => 'Створити адміністратора',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Дозволені валюти',
                'allowed-locales'     => 'Дозволені локалізації',
                'application-name'    => 'Назва додатку',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Китайський юань (CNY)',
                'database-connection' => 'З’єднання з базою даних',
                'database-hostname'   => 'Ім’я хоста бази даних',
                'database-name'       => 'Назва бази даних',
                'database-password'   => 'Пароль бази даних',
                'database-port'       => 'Порт бази даних',
                'database-prefix'     => 'Префікс бази даних',
                'database-username'   => 'Користувацьке ім’я бази даних',
                'default-currency'    => 'Валюта за замовчуванням',
                'default-locale'      => 'Локалізація за замовчуванням',
                'default-timezone'    => 'Часовий пояс за замовчуванням',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL за замовчуванням',
                'dirham'              => 'Дирхам (AED)',
                'euro'                => 'Євро (EUR)',
                'iranian'             => 'Іранський ріал (IRR)',
                'israeli'             => 'Ізраїльський шекель (ILS)',
                'japanese-yen'        => 'Японська єна (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Британський фунт (GBP)',
                'rupee'               => 'Індійська рупія (INR)',
                'russian-ruble'       => 'Російський рубль (RUB)',
                'saudi'               => 'Саудівський ріал (SAR)',
                'select-timezone'     => 'Оберіть часовий пояс',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'З’єднання з базою даних',
                'turkish-lira'        => 'Турецька ліра (TRY)',
                'ukrainian-hryvnia'   => 'Українська гривня (UAH)',
                'usd'                 => 'Долар США (USD)',
                'warning-message'     => 'Увага! Це значення більше не можна змінити - валюта за замовчуванням та локалізація.',
            ],

            'installation-processing' => [
                'unopim'      => 'Установка UnoPim',
                'unopim-info' => 'Створення таблиць бази даних може зайняти кілька хвилин.',
                'title'       => 'Процес установки',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Панель адміністратора',
                'unopim-forums'             => 'Форуми UnoPim',
                'explore-unopim-extensions' => 'Досліджуйте розширення UnoPim',
                'title-info'                => 'UnoPim успішно встановлено.',
                'title'                     => 'Установка завершена',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Створити таблиці бази даних',
                'install-info-button'     => 'Натисніть цю кнопку, щоб почати',
                'install-info'            => 'установку UnoPim',
                'install'                 => 'Встановити',
                'populate-database-table' => 'Заповнити таблиці бази даних',
                'start-installation'      => 'Почати установку',
                'title'                   => 'Готово до установки',
            ],

            'start' => [
                'locale'        => 'Локалізація',
                'main'          => 'Головне',
                'select-locale' => 'Оберіть мову',
                'title'         => 'Установка UnoPim',
                'welcome-title' => 'Ласкаво просимо до UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Календар',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Файлова інформація',
                'filter'      => 'Фільтр',
                'gd'          => 'GD',
                'hash'        => 'Хеш',
                'intl'        => 'Intl',
                'json'        => 'JSON',
                'mbstring'    => 'MbString',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 або вище',
                'php'         => 'PHP',
                'session'     => 'Сеанс',
                'title'       => 'Системні вимоги',
                'tokenizer'   => 'Токенізатор',
                'xml'         => 'XML',
            ],

            'back'                     => 'Назад',
            'unopim-info'              => 'Проєкт громади',
            'unopim-logo'              => 'Логотип UnoPim',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Продовжити',
            'installation-description' => 'Установка UnoPim складається з кількох кроків. Ось короткий огляд:',
            'wizard-language'          => 'Мова майстра установки',
            'installation-info'        => 'Дякуємо за приєднання!',
            'installation-title'       => 'Встановлення UnoPim',
            'save-configuration'       => 'Зберегти конфігурацію',
            'skip'                     => 'Пропустити',
            'title'                    => 'Майстер установки UnoPim',
            'webkul'                   => 'Webkul',
        ],
    ],
];
