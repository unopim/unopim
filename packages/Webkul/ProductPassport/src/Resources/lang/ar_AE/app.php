<?php

return [
    'type' => [
        'label' => 'جواز المنتج الرقمي',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'جواز المنتج',
            'info'     => 'إعدادات نشر جواز المنتج الرقمي.',
            'settings' => [
                'title'                              => 'إعدادات جواز المنتج',
                'enabled'                            => 'مفعّل',
                'enabled-hint'                       => 'تفعيل ميزة جواز المنتج الرقمي لهذا الكتالوج. عند إيقافها، تُخفى لوحة الجواز والشبكة.',
                'auto-publish'                       => 'النشر التلقائي عند الحفظ',
                'auto-publish-hint'                  => 'نشر نسخة من الجواز تلقائيًا كلما تم حفظ منتج واستوفى حد الاكتمال. اتركه معطّلًا للنشر يدويًا.',
                'completeness-threshold'             => 'حد اكتمال البيانات (%)',
                'completeness-threshold-hint'        => 'الحد الأدنى لاكتمال بيانات المنتج، بالنسبة المئوية، المطلوب قبل أن يُنشَر جواز للغة معينة.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'اسم المشغل الاقتصادي',
                'operator-name-hint'                 => 'الاسم القانوني للمصنّع أو المشغل الاقتصادي المسؤول، ويظهر على كل جواز عام وفقًا لما تقتضيه لائحة ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'عنوان المشغل الاقتصادي',
                'operator-address-hint'              => 'العنوان البريدي المسجَّل للمشغل الاقتصادي، ويظهر على الجواز العام لأغراض التتبّع.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'الممثل المعتمد في الاتحاد الأوروبي',
                'operator-eu-rep-hint'               => 'اسم وجهة اتصال الممثل المعتمد في الاتحاد الأوروبي، مطلوب عندما يكون المصنّع مقيمًا خارج الاتحاد الأوروبي.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'رابط الدعم',
                'support-url-hint'                   => 'صفحة عامة يمكن للعملاء العثور فيها على المساعدة أو معلومات الضمان. تظهر كرابط على كل جواز.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'جواز السفر الرقمي للمنتج',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'تركيبة المواد',
        'dpp_substances_of_concern'     => 'المواد المثيرة للقلق',
        'dpp_recycled_content_pct'      => 'نسبة المحتوى المعاد تدويره (%)',
        'dpp_carbon_footprint'          => 'البصمة الكربونية',
        'dpp_energy_consumption'        => 'استهلاك الطاقة',
        'dpp_durability_statement'      => 'بيان المتانة',
        'dpp_repairability_score'       => 'درجة قابلية الإصلاح',
        'dpp_spare_parts_availability'  => 'توفر قطع الغيار',
        'dpp_care_instructions'         => 'تعليمات العناية',
        'dpp_disassembly_guide'         => 'دليل التفكيك',
        'dpp_manufacturer_name'         => 'اسم الشركة المصنعة',
        'dpp_manufacturing_site'        => 'موقع التصنيع',
        'dpp_country_of_origin'         => 'بلد المنشأ',
        'dpp_supply_chain_notes'        => 'ملاحظات سلسلة التوريد',
        'dpp_end_of_life_instructions'  => 'تعليمات نهاية العمر',
        'dpp_take_back_scheme'          => 'برنامج الاسترجاع',
        'dpp_declaration_of_conformity' => 'إعلان المطابقة',
        'dpp_test_reports'              => 'تقارير الاختبار',
        'dpp_certificates'              => 'الشهادات',
        'dpp_gtin'                      => 'الرقم الدولي للمنتج (GTIN)',
        'dpp_model_identifier'          => 'معرف الطراز',
        'dpp_batch_identifier'          => 'معرف الدفعة',
        'dpp_warranty_terms'            => 'شروط الضمان',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'تم تثبيت سمات جواز السفر الرقمي للمنتج بنجاح.',
        ],
    ],

    'public' => [
        'badge'         => 'جواز المنتج الرقمي من EU',
        'search-locale' => 'لغة البحث',
        'sections'      => [
            'passport' => 'جواز المنتج',
        ],
        'title'      => 'جواز المنتج الرقمي',
        'identifier' => [
            'title'        => 'التعريف',
            'gtin'         => 'GTIN',
            'model'        => 'الطراز',
            'batch'        => 'الدفعة',
            'not-provided' => 'غير متوفر',
        ],
        'operator' => [
            'title' => 'المشغل الاقتصادي',
        ],
        'documents' => [
            'title' => 'المستندات',
        ],
    ],

    'publications' => [
        'not-found'      => 'لا يوجد جواز منتج بالمعرّف :id.',
        'index'          => [
            'disabled-notice' => 'نشر الجوازات معطل حاليًا. تظهر الجوازات الحالية أدناه لإدارتها (العرض والسحب).',
            'title'           => 'جوازات المنتج الرقمية',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'القناة',
            'status'          => 'الحالة',
            'live-locales'    => 'اللغات النشطة',
            'last-published'  => 'آخر نشر',
            'withdraw'        => 'سحب',
            'mass-publish'    => 'نشر المحدد',
        ],
        'publish-queued'      => 'تمت جدولة نشر الجواز.',
        'bulk-publish-queued' => 'تمت جدولة نشر جوازات المنتجات المحددة.',
        'withdrawn'           => 'تم سحب الجواز بنجاح.',
        'mass-publish'        => [
            'action' => 'نشر جواز المنتج الرقمي',
            'queued' => 'تمت جدولة نشر الجواز لـ :count منتج.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'الجوازات',
            'view'     => 'عرض',
            'publish'  => 'نشر',
            'withdraw' => 'سحب',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'الجوازات',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'جارٍ النشر…',
                    'queued'               => 'في قائمة الانتظار',
                    'copy-operator-link'   => 'نسخ رابط المُشغِّل',
                    'copy-authority-link'  => 'نسخ رابط السلطة',
                    'link-copied'          => 'تم نسخ الرابط',
                    'download-qr'          => 'تنزيل رمز QR',
                    'title'                => 'جواز المنتج الرقمي',
                    'publishing-disabled'  => 'نشر الجواز معطل لهذه القناة.',
                    'locale'               => 'اللغة',
                    'version'              => 'الإصدار',
                    'published-at'         => 'تاريخ النشر',
                    'missing-fields'       => 'الحقول الناقصة',
                    'not-published'        => 'غير منشور',
                    'unscored'             => 'غير مقيّم',
                    'publish'              => 'نشر',
                    'republish'            => 'إعادة النشر',
                    'publish-all'          => 'نشر جميع اللغات',
                    'auto-publish-on'      => 'النشر التلقائي مفعّل — يتم نشر الجوازات تلقائيًا عند حفظ المنتج واستيفائه حد الاكتمال. استخدم الأزرار للنشر الآن.',
                    'auto-publish-off'     => 'النشر اليدوي — استخدم الأزرار لنشر جواز هذا المنتج لكل لغة.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'يجب أن يكون :attribute رقم GTIN صالحًا (8 أو 12 أو 13 أو 14 رقمًا مع رقم تحقق صحيح).',
    ],
    'mapping' => [
        'title'         => 'ربط حقول جواز المنتج',
        'info'          => 'اسند كل حقل من حقول جواز المنتج إلى سمة تحتفظ بها بالفعل. اترك الحقل غير مرتبط للرجوع إلى سمة الجواز المخصصة له.',
        'menu'          => 'ربط الحقول',
        'field'         => 'حقل الجواز',
        'source'        => 'السمة المصدر',
        'select-source' => 'استخدام سمة الجواز',
        'save-btn'      => 'حفظ الربط',
        'type-mismatch' => 'المصدر المحدد غير متوافق مع نوع حقل جواز المنتج هذا.',
        'saved'         => 'تم حفظ ربط الحقول بنجاح.',
    ],

];
