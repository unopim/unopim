<?php

return [
    'exporters' => [
        'shopify' => [
            'product'  => 'Shopify उत्पाद',
            'category' => 'Shopify श्रेणी',
        ],
    ],

    'importers' => [
        'shopify' => [
            'product'  => 'शोपिफाई उत्पाद',
            'category' => 'शोपिफाई श्रेणी',
            'attribute'=> 'शोपिफाई विशेषता',
            'family'   => 'शोपिफाई परिवार',
            'metafield'=> 'शॉपिफ़ाई मेटाफ़ील्ड परिभाषाएँ',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'shopify'         => 'Shopify',
                'credentials'     => 'प्रमाण पत्र',
                'export-mappings' => 'निर्यात मानचित्रण',
                'import-mappings' => 'आयात मैपिंग्स',
                'settings'        => 'सेटिंग्स',
            ],
        ],
    ],

    'shopify' => [
        'acl' => [
            'credential' => [
                'create' => 'बनाएँ',
                'edit'   => 'संपादित करें',
                'delete' => 'हटाएँ',
            ],

            'metafield' => [
                'create'      => 'मेटाफील्ड बनाएं',
                'edit'        => 'मेटाफील्ड संपादित करें',
                'delete'      => 'मेटाफील्ड हटाएं',
                'mass_delete' => 'मेटाफील्ड को सामूहिक रूप से हटाएं',
            ],
        ],

        'version' => 'संस्करण: 1.0.0',

        'credential' => [
            'export' => [
                'locales' => 'स्थानीय मानचित्रण',
            ],
            'shopify' => [
                'locale' => 'Shopify भाषा',
            ],
            'unopim' => [
                'locale' => 'Unopim भाषा',
            ],
            'delete-success' => 'प्रमाण पत्र सफलतापूर्वक हटाया गया',
            'created'        => 'प्रमाण पत्र सफलतापूर्वक बनाया गया',
            'update-success' => 'सफलतापूर्वक अपडेट किया गया',
            'invalid'        => 'अमान्य प्रमाण पत्र',
            'invalidurl'     => 'अमान्य URL',
            'already_taken'  => 'शॉप यूआरएल पहले ही लिया जा चुका है।',
            'index'          => [
                'title'                 => 'Shopify प्रमाण पत्र',
                'create'                => 'प्रमाण पत्र बनाएं',
                'url'                   => 'Shopify URL',
                'shopifyurlplaceholder' => 'Shopify URL (उदा. http://demo.myshopify.com)',
                'accesstoken'           => 'एडमिन API एक्सेस टोकन',
                'apiVersion'            => 'API संस्करण',
                'save'                  => 'सहेजें',
                'back-btn'              => 'वापस',
                'channel'               => 'प्रकाशन (बिक्री चैनल)',
                'locations'             => 'स्थान सूची',
            ],
            'edit' => [
                'title'    => 'प्रमाण पत्र संपादित करें',
                'delete'   => 'प्रमाण पत्र हटाएं',
                'back-btn' => 'वापस',
                'update'   => 'अपडेट करें',
                'save'     => 'सहेजें',
            ],
            'datagrid' => [
                'shopUrl'    => 'Shopify URL',
                'apiVersion' => 'API संस्करण',
                'enabled'    => 'सक्रिय करें',
            ],
        ],
        'export' => [
            'mapping' => [
                'title'         => 'निर्यात मानचित्रण',
                'back-btn'      => 'वापस',
                'save'          => 'सहेजें',
                'created'       => 'निर्यात मानचित्रण बनाया गया',
                'image'         => 'छवि के रूप में उपयोग की जाने वाली विशेषता',
                'metafields'    => 'मेटाफील्ड के रूप में उपयोग की जाने वाली विशेषताएँ',
                'filed-shopify' => 'Shopify में फ़ील्ड',
                'attribute'     => 'विशेषता',
                'fixed-value'   => 'स्थिर मान',
            ],
            'setting' => [
                'title'                        => 'सेटिंग',
                'tags'                         => 'टैग निर्यात सेटिंग',
                'enable_metric_tags_attribute' => 'क्या आप टैग में मेट्रिक इकाई नाम को भी शामिल करना चाहते हैं?',
                'enable_named_tags_attribute'  => 'क्या आप टैग को नामित टैग के रूप में लाना चाहते हैं?',
                'tagSeprator'                  => 'टैग में विशेषता नाम विभाजक का उपयोग करें',
                'enable_tags_attribute'        => 'क्या आप टैग में विशेषता नाम को भी शामिल करना चाहते हैं?',
                'metafields'                   => 'मेटाफील्ड निर्यात सेटिंग',
                'metaFieldsKey'                => 'मेटाफील्ड कुंजी के रूप में विशेषता कोड/लेबल का उपयोग करें',
                'metaFieldsNameSpace'          => 'मेटाफील्ड के नामस्थान के रूप में विशेषता समूह कोड/वैश्विक का उपयोग करें',
                'other-settings'               => 'अन्य सेटिंग्स',
                'roundof-attribute-value'      => 'मेट्रिक विशेषता मान के अतिरिक्त अंशात्मक शून्य हटाएं (उदा. 201.2000 को 201.2 के रूप में दिखाएं)',
                'option_name_label'            => 'विकल्प नाम के लिए मान को विशेषता लेबल के रूप में दिखाएं (डिफ़ॉल्ट रूप से विशेषता कोड)',
            ],

            'errors' => [
                'invalid-credential' => 'अमान्य क्रेडेंशियल। क्रेडेंशियल या तो अक्षम है या गलत है',
            ],
        ],
        'import' => [
            'mapping' => [
                'title'                => 'आयात मैपिंग्स',
                'back-btn'             => 'वापस',
                'save'                 => 'सहेजें',
                'created'              => 'आयात मैपिंग सफलतापूर्वक सहेजा गया',
                'image'                => 'चित्र के रूप में उपयोग करने के लिए गुण',
                'filed-shopify'        => 'Shopify में फ़ील्ड',
                'attribute'            => 'UnoPim गुण',
                'variantimage'         => 'वेरिएंट छवि के रूप में उपयोग करने के लिए गुण',
                'other'                => 'Shopify अन्य मैपिंग्स',
                'family'               => 'परिवार मैपिंग (उत्पादों के लिए)',
                'metafieldDefinitions' => 'Shopify मेटाफील्ड परिभाषा मैपिंग',
            ],
            'setting' => [
                'credentialmapping' => 'क्रेडेंशियल मैपिंग',
            ],
            'job' => [
                'product' => [
                    'family-not-exist'      => 'शीर्षक के लिए परिवार मौजूद नहीं है:- :title पहले आपको परिवार आयात करना होगा',
                    'variant-sku-not-exist' => 'वेरिएंट SKU उत्पाद में नहीं मिला:- :id',
                    'duplicate-sku'         => ':sku :- उत्पाद में डुप्लिकेट SKU पाया गया',
                    'required-field'        => ':attribute :- फ़ील्ड SKU के लिए आवश्यक है:- :sku',
                    'family-not-mapping'    => 'शीर्षक के लिए परिवार मैप नहीं किया गया है:- :title',
                    'attribute-not-exist'   => ':attributes गुण उत्पाद के लिए मौजूद नहीं हैं',
                    'not-found-sku'         => 'उत्पाद में SKU नहीं मिला:- :id',
                    'option-not-found'      => ':attribute - :option विकल्प UnoPim SKU में नहीं मिला:- :sku',
                ],
            ],
        ],

        'fields' => [
            'name'                        => 'नाम',
            'description'                 => 'विवरण',
            'price'                       => 'मूल्य',
            'weight'                      => 'वजन',
            'quantity'                    => 'मात्रा',
            'inventory_tracked'           => 'भंडार ट्रैक किया गया',
            'allow_purchase_out_of_stock' => 'स्टॉक में न होने पर भी खरीदने की अनुमति दें',
            'vendor'                      => 'विक्रेता',
            'product_type'                => 'उत्पाद प्रकार',
            'tags'                        => 'टैग',
            'barcode'                     => 'बारकोड',
            'compare_at_price'            => 'कीमत की तुलना करें',
            'seo_title'                   => 'SEO शीर्षक',
            'seo_description'             => 'SEO विवरण',
            'handle'                      => 'Handle',
            'taxable'                     => 'कर योग्य',
            'inventory_cost'              => 'भंडार लागत',
        ],
        'exportmapping' => 'विशेषता मानचित्रण',
        'job'           => [
            'credentials'      => 'Shopify प्रमाण-पत्र',
            'channel'          => 'चैनल',
            'currency'         => 'मुद्रा',
            'productfilter'    => 'उत्पाद फ़िल्टर (SKU)',
            'locale'           => 'भाषा',
            'attribute-groups' => 'गुण समूह',
        ],
    ],
];
