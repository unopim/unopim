<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'تقصير',
            ],

            'attribute-groups'   => [
                'description'       => 'وصف',
                'general'           => 'عام',
                'inventories'       => 'المخزونات',
                'meta-description'  => 'وصف ميتا',
                'price'             => 'سعر',
                'technical'         => 'اِصطِلاحِيّ',
                'shipping'          => 'شحن',
            ],

            'attributes'         => [
                'brand'                => 'ماركة',
                'color'                => 'لون',
                'cost'                 => 'يكلف',
                'description'          => 'وصف',
                'featured'             => 'مميز',
                'guest-checkout'       => 'الخروج الضيف',
                'height'               => 'ارتفاع',
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
                'status'               => 'حالة',
                'tax-category'         => 'فئة الضريبة',
                'url-key'              => 'مفتاح URL',
                'visible-individually' => 'مرئية بشكل فردي',
                'weight'               => 'وزن',
                'width'                => 'عرض',
            ],

            'attribute-options'  => [
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

        'category'  => [
            'categories' => [
                'description' => 'وصف فئة الجذر',
                'name'        => 'جذر',
            ],

            'category_fields' => [
                'name'        => 'اسم',
                'description' => 'وصف',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'معلومات عنا محتوى الصفحة',
                    'title'   => 'معلومات عنا',
                ],

                'contact-us'       => [
                    'content' => 'اتصل بنا محتوى الصفحة',
                    'title'   => 'اتصل بنا',
                ],

                'customer-service' => [
                    'content' => 'محتوى صفحة خدمة العملاء',
                    'title'   => 'خدمة العملاء',
                ],

                'payment-policy'   => [
                    'content' => 'محتوى صفحة سياسة الدفع',
                    'title'   => 'سياسة الدفع',
                ],

                'privacy-policy'   => [
                    'content' => 'محتوى صفحة سياسة الخصوصية',
                    'title'   => 'سياسة الخصوصية',
                ],

                'refund-policy'    => [
                    'content' => 'محتوى صفحة سياسة استرداد الأموال',
                    'title'   => 'سياسة استرداد الأموال',
                ],

                'return-policy'    => [
                    'content' => 'محتوى صفحة سياسة الإرجاع',
                    'title'   => 'سياسة العائدات',
                ],

                'shipping-policy'  => [
                    'content' => 'محتوى صفحة سياسة الشحن',
                    'title'   => 'سياسة الشحن',
                ],

                'terms-conditions' => [
                    'content' => 'الشروط والأحكام محتوى الصفحة',
                    'title'   => 'الشروط والأحكام',
                ],

                'terms-of-use'     => [
                    'content' => 'شروط الاستخدام محتوى الصفحة',
                    'title'   => 'شروط الاستخدام',
                ],

                'whats-new'        => [
                    'content' => 'ما هو محتوى الصفحة الجديد',
                    'title'   => 'ما هو الجديد',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
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

        'customer'  => [
            'customer-groups' => [
                'general'   => 'عام',
                'guest'     => 'ضيف',
                'wholesale' => 'بالجملة',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'تقصير',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'جميع المنتجات',

                    'options' => [
                        'title' => 'جميع المنتجات',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'عرض الكل',
                        'description' => 'نقدم لكم مجموعاتنا الجريئة الجديدة! ارتقي بأسلوبك من خلال التصاميم الجريئة والبيانات النابضة بالحياة. اكتشف الأنماط المذهلة والألوان الجريئة التي تعيد تعريف خزانة ملابسك. استعد لاحتضان ما هو غير عادي!',
                        'title'       => 'استعدوا لمجموعاتنا الجريئة الجديدة!',
                    ],

                    'name'    => 'مجموعات جريئة',
                ],

                'categories-collections' => [
                    'name' => 'الفئات المجموعات',
                ],

                'featured-collections'   => [
                    'name'    => 'مجموعات مميزة',

                    'options' => [
                        'title' => 'المنتجات المميزة',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'روابط التذييل',

                    'options' => [
                        'about-us'         => 'معلومات عنا',
                        'contact-us'       => 'اتصل بنا',
                        'customer-service' => 'خدمة العملاء',
                        'payment-policy'   => 'سياسة الدفع',
                        'privacy-policy'   => 'سياسة الخصوصية',
                        'refund-policy'    => 'سياسة استرداد الأموال',
                        'return-policy'    => 'سياسة العائدات',
                        'shipping-policy'  => 'سياسة الشحن',
                        'terms-conditions' => 'الشروط والأحكام',
                        'terms-of-use'     => 'شروط الاستخدام',
                        'whats-new'        => 'ما هو الجديد',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'مجموعاتنا',
                        'sub-title-2' => 'مجموعاتنا',
                        'title'       => 'اللعبة مع إضافاتنا الجديدة!',
                    ],

                    'name'    => 'حاوية اللعبة',
                ],

                'image-carousel'         => [
                    'name'    => 'صورة دائري',

                    'sliders' => [
                        'title' => 'استعد للمجموعة الجديدة',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'منتجات جديدة',

                    'options' => [
                        'title' => 'منتجات جديدة',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'احصل على خصم يصل إلى 40% على طلبك الأول تسوق الآن',
                    ],

                    'name' => 'معلومات العرض',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'لا تتوفر تكلفة EMI على جميع بطاقات الائتمان الرئيسية',
                        'free-shipping-info'   => 'استمتع بالشحن المجاني لجميع الطلبات',
                        'product-replace-info' => 'سهولة استبدال المنتج متاحة!',
                        'time-support-info'    => 'دعم مخصص على مدار 24 ساعة طوال أيام الأسبوع عبر الدردشة والبريد الإلكتروني',
                    ],

                    'name'        => 'محتوى الخدمات',

                    'title'       => [
                        'emi-available'   => 'إيمي متاح',
                        'free-shipping'   => 'ًالشحن مجانا',
                        'product-replace' => 'استبدال المنتج',
                        'time-support'    => 'دعم 24/7',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'مجموعاتنا',
                        'sub-title-2' => 'مجموعاتنا',
                        'sub-title-3' => 'مجموعاتنا',
                        'sub-title-4' => 'مجموعاتنا',
                        'sub-title-5' => 'مجموعاتنا',
                        'sub-title-6' => 'مجموعاتنا',
                        'title'       => 'اللعبة مع إضافاتنا الجديدة!',
                    ],

                    'name'    => 'أفضل المجموعات',
                ],
            ],
        ],

        'user'      => [
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

            'installation-processing'   => [
                'unopim'            => 'تثبيت أونوبيم',
                'unopim-info'       => 'إنشاء جداول قاعدة البيانات، قد يستغرق ذلك بضع دقائق',
                'title'             => 'تثبيت',
            ],

            'installation-completed'    => [
                'admin-panel'                   => 'لوحة الإدارة',
                'unopim-forums'                 => 'منتدى أونوبيم',
                'explore-unopim-extensions'     => 'اكتشف ملحق UnoPim',
                'title-info'                    => 'تم تثبيت UnoPim بنجاح على نظامك.',
                'title'                         => 'اكتمل التثبيت',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'إنشاء جدول قاعدة البيانات',
                'install-info-button'     => 'انقر فوق الزر أدناه ل',
                'install-info'            => 'UnoPim للتثبيت',
                'install'                 => 'تثبيت',
                'populate-database-table' => 'تعبئة جداول قاعدة البيانات',
                'start-installation'      => 'ابدأ التثبيت',
                'title'                   => 'جاهز للتثبيت',
            ],

            'start'                     => [
                'locale'        => 'لغة',
                'main'          => 'يبدأ',
                'select-locale' => 'حدد اللغة',
                'title'         => 'تثبيت UnoPim الخاص بك',
                'welcome-title' => 'مرحبا بكم في UnoPim :version',
            ],

            'server-requirements'       => [
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

            'arabic'                    => 'عربي',
            'back'                      => 'خلف',
            'unopim-info'               => 'مشروع مجتمعي بقلم',
            'unopim-logo'               => 'شعار أونوبيم',
            'unopim'                    => 'Unopim',
            'bengali'                   => 'البنغالية',
            'chinese'                   => 'الصينية',
            'continue'                  => 'يكمل',
            'dutch'                     => 'هولندي',
            'english'                   => 'إنجليزي',
            'french'                    => 'فرنسي',
            'german'                    => 'الألمانية',
            'hebrew'                    => 'العبرية',
            'hindi'                     => 'الهندية',
            'installation-description'  => 'يتضمن تثبيت UnoPim عادةً عدة خطوات. فيما يلي مخطط عام لعملية تثبيت UnoPim:',
            'wizard-language'           => 'لغة معالج التثبيت',
            'installation-info'         => 'نحن سعداء لرؤيتك هنا!',
            'installation-title'        => 'مرحبا بكم في التثبيت',
            'italian'                   => 'ايطالي',
            'japanese'                  => 'اليابانية',
            'persian'                   => 'الفارسية',
            'polish'                    => 'بولندي',
            'portuguese'                => 'البرتغالية البرازيلية',
            'russian'                   => 'الروسية',
            'save-configuration'        => 'حفظ التكوين',
            'sinhala'                   => 'السنهالية',
            'skip'                      => 'يتخطى',
            'spanish'                   => 'الأسبانية',
            'title'                     => 'المثبت أونوبيم',
            'turkish'                   => 'تركي',
            'ukrainian'                 => 'الأوكرانية',
            'webkul'                    => 'Webkul',
        ],
    ],
];
