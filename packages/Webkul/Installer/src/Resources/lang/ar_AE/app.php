<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'تقصير',
            ],

            'attribute-groups' => [
                'description'      => 'وصف',
                'general'          => 'عام',
                'meta-description' => 'وصف ميتا',
                'price'            => 'السعر',
                'media'            => 'وسائط',
            ],

            'attributes' => [
                'brand'                => 'ماركة',
                'color'                => 'لون',
                'cost'                 => 'يكلف',
                'description'          => 'وصف',
                'featured'             => 'مميز',
                'guest-checkout'       => 'الخروج الضيف',
                'height'               => 'ارتفاع',
                'image'                => 'صورة',
                'length'               => 'طول',
                'manage-stock'         => 'إدارة المخزون',
                'meta-description'     => 'وصف ميتا',
                'meta-keywords'        => 'الكلمات الرئيسية التعريفية',
                'meta-title'           => 'عنوان ميتا',
                'name'                 => 'اسم',
                'new'                  => 'جديد',
                'price'                => 'سعر',
                'product-number'       => 'رقم المنتج',
                'short-description'    => 'وصف قصير',
                'size'                 => 'مقاس',
                'sku'                  => 'رمز التخزين التعريفي',
                'special-price-from'   => 'سعر خاص من',
                'special-price-to'     => 'سعر خاص ل',
                'special-price'        => 'سعر خاص',
                'tax-category'         => 'فئة الضريبة',
                'url-key'              => 'مفتاح URL',
                'visible-individually' => 'مرئية بشكل فردي',
                'weight'               => 'وزن',
                'width'                => 'عرض',
            ],

            'attribute-options' => [
                'black'  => 'أسود',
                'green'  => 'أخضر',
                'l'      => 'ل',
                'm'      => 'م',
                'red'    => 'أحمر',
                's'      => 'س',
                'white'  => 'أبيض',
                'xl'     => 'XL',
                'yellow' => 'أصفر',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'وصف فئة الجذر',
                'name'        => 'جذر',
            ],

            'category_fields' => [
                'name'        => 'اسم',
                'description' => 'وصف',
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'متجر تجريبي',
                'meta-keywords'    => 'الكلمة الرئيسية التعريفية للمتجر التجريبي',
                'meta-description' => 'الوصف التعريفي للمتجر التجريبي',
                'name'             => 'تقصير',
            ],

            'currencies' => [
                'AED' => 'درهم',
                'AFN' => 'الشيكل الإسرائيلي',
                'CNY' => 'اليوان الصيني',
                'EUR' => 'اليورو',
                'GBP' => 'الجنيه الاسترليني',
                'INR' => 'الروبية الهندية',
                'IRR' => 'الريال الإيراني',
                'JPY' => 'الين الياباني',
                'RUB' => 'الروبل الروسي',
                'SAR' => 'الريال السعودي',
                'TRY' => 'الليرة التركية',
                'UAH' => 'الهريفنيا الأوكرانية',
                'USD' => 'الدولار الأمريكي',
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'سيكون لمستخدمي هذا الدور حق الوصول الكامل',
                'name'        => 'المسؤول',
            ],

            'users' => [
                'name' => 'مثال',
            ],
        ],
    ],

    'installer' => [

        'middleware' => [
            'already-installed' => 'التطبيق مثبت بالفعل.',
        ],

        'index' => [
            'create-administrator' => [
                'admin'            => 'مسؤل',
                'unopim'           => 'أونوبيم',
                'confirm-password' => 'تأكيد كلمة المرور',
                'email-address'    => 'admin@example.com',
                'email'            => 'بريد إلكتروني',
                'password'         => 'كلمة المرور',
                'title'            => 'إنشاء المسؤول',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'العملات المسموح بها',
                'allowed-locales'     => 'اللغات المسموح بها',
                'application-name'    => 'اسم التطبيق',
                'unopim'              => 'أونوبيم',
                'chinese-yuan'        => 'اليوان الصيني (CNY)',
                'database-connection' => 'اتصال قاعدة البيانات',
                'database-hostname'   => 'اسم مضيف قاعدة البيانات',
                'database-name'       => 'اسم قاعدة البيانات',
                'database-password'   => 'كلمة مرور قاعدة البيانات',
                'database-port'       => 'منفذ قاعدة البيانات',
                'database-prefix'     => 'بادئة قاعدة البيانات',
                'database-username'   => 'اسم مستخدم قاعدة البيانات',
                'default-currency'    => 'العملة الافتراضية',
                'default-locale'      => 'اللغة الافتراضية',
                'default-timezone'    => 'المنطقة الزمنية الافتراضية',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'عنوان URL الافتراضي',
                'dirham'              => 'درهم (درهم)',
                'euro'                => 'اليورو (يورو)',
                'iranian'             => 'الريال الإيراني (IRR)',
                'israeli'             => 'الشيكل الإسرائيلي (AFN)',
                'japanese-yen'        => 'الين الياباني (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'الجنيه الاسترليني (GBP)',
                'rupee'               => 'الروبية الهندية (INR)',
                'russian-ruble'       => 'الروبل الروسي (RUB)',
                'saudi'               => 'الريال السعودي (SAR)',
                'select-timezone'     => 'حدد المنطقة الزمنية',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'تكوين قاعدة البيانات',
                'turkish-lira'        => 'الليرة التركية (TRY)',
                'ukrainian-hryvnia'   => 'الهريفنيا الأوكرانية (UAH)',
                'usd'                 => 'الدولار الأمريكي (USD)',
                'warning-message'     => 'احذر! تعد إعدادات لغات النظام الافتراضية وكذلك العملة الافتراضية دائمة ولا يمكن تغييرها مرة أخرى.',
            ],

            'installation-processing' => [
                'unopim'      => 'تثبيت أونوبيم',
                'unopim-info' => 'إنشاء جداول قاعدة البيانات، قد يستغرق ذلك بضع دقائق',
                'title'       => 'تثبيت',
            ],

            'installation-completed' => [
                'admin-panel'               => 'لوحة الإدارة',
                'unopim-forums'             => 'منتدى أونوبيم',
                'explore-unopim-extensions' => 'اكتشف ملحق UnoPim',
                'title-info'                => 'تم تثبيت UnoPim بنجاح على نظامك.',
                'title'                     => 'اكتمل التثبيت',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'إنشاء جدول قاعدة البيانات',
                'install-info-button'     => 'انقر فوق الزر أدناه ل',
                'install-info'            => 'UnoPim للتثبيت',
                'install'                 => 'تثبيت',
                'populate-database-table' => 'تعبئة جداول قاعدة البيانات',
                'start-installation'      => 'ابدأ التثبيت',
                'title'                   => 'جاهز للتثبيت',
            ],

            'start' => [
                'locale'        => 'لغة',
                'main'          => 'يبدأ',
                'select-locale' => 'حدد اللغة',
                'title'         => 'تثبيت UnoPim الخاص بك',
                'welcome-title' => 'مرحبا بكم في UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'تقويم',
                'ctype'       => 'cType',
                'curl'        => 'حليقة',
                'dom'         => 'دوم',
                'fileinfo'    => 'fileInfo',
                'filter'      => 'فلتر',
                'gd'          => 'جي دي',
                'hash'        => 'التجزئة',
                'intl'        => 'الدولي',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'opensl',
                'pcre'        => 'pcre',
                'pdo'         => 'pdo',
                'php-version' => '8.2 أو أعلى',
                'php'         => 'PHP',
                'session'     => 'حصة',
                'title'       => 'متطلبات النظام',
                'tokenizer'   => 'رمز مميز',
                'xml'         => 'XML',
            ],

            'back'                     => 'خلف',
            'unopim-info'              => 'مشروع مجتمعي بقلم',
            'unopim-logo'              => 'شعار أونوبيم',
            'unopim'                   => 'Unopim',
            'continue'                 => 'يكمل',
            'installation-description' => 'يتضمن تثبيت UnoPim عادةً عدة خطوات. فيما يلي مخطط عام لعملية تثبيت UnoPim:',
            'wizard-language'          => 'لغة معالج التثبيت',
            'installation-info'        => 'نحن سعداء لرؤيتك هنا!',
            'installation-title'       => 'مرحبا بكم في التثبيت',
            'save-configuration'       => 'حفظ التكوين',
            'skip'                     => 'يتخطى',
            'title'                    => 'المثبت أونوبيم',
            'webkul'                   => 'Webkul',
        ],
    ],
];
