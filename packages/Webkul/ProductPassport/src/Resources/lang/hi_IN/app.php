<?php

return [
    'type' => [
        'label' => 'डिजिटल उत्पाद पासपोर्ट',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'उत्पाद पासपोर्ट',
            'info'     => 'डिजिटल उत्पाद पासपोर्ट के प्रकाशन की सेटिंग्स।',
            'settings' => [
                'title'                              => 'उत्पाद पासपोर्ट सेटिंग्स',
                'enabled'                            => 'सक्षम',
                'enabled-hint'                       => 'इस कैटलॉग के लिए डिजिटल उत्पाद पासपोर्ट सुविधा चालू करें। बंद होने पर, पासपोर्ट पैनल और ग्रिड छिप जाते हैं।',
                'auto-publish'                       => 'सहेजते समय स्वतः प्रकाशित करें',
                'auto-publish-hint'                  => 'जब भी कोई उत्पाद सहेजा जाता है और पूर्णता सीमा को पूरा करता है, तो पासपोर्ट संस्करण स्वतः प्रकाशित करें। मैन्युअल रूप से प्रकाशित करने के लिए इसे बंद रखें।',
                'completeness-threshold'             => 'पूर्णता सीमा (%)',
                'completeness-threshold-hint'        => 'किसी भाषा के लिए पासपोर्ट प्रकाशित किए जाने से पहले आवश्यक न्यूनतम उत्पाद पूर्णता, प्रतिशत में।',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'आर्थिक ऑपरेटर का नाम',
                'operator-name-hint'                 => 'निर्माता या ज़िम्मेदार आर्थिक ऑपरेटर का कानूनी नाम, ESPR विनियमन के अनुसार हर सार्वजनिक पासपोर्ट पर दिखाया जाता है।',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'आर्थिक ऑपरेटर का पता',
                'operator-address-hint'              => 'आर्थिक ऑपरेटर का पंजीकृत डाक पता, ट्रेसेबिलिटी के लिए सार्वजनिक पासपोर्ट पर दिखाया जाता है।',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'EU अधिकृत प्रतिनिधि',
                'operator-eu-rep-hint'               => 'EU अधिकृत प्रतिनिधि का नाम और संपर्क, तब आवश्यक जब निर्माता EU के बाहर स्थापित हो।',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'सहायता URL',
                'support-url-hint'                   => 'सार्वजनिक पृष्ठ जहाँ ग्राहक सहायता या वारंटी जानकारी पा सकते हैं। हर पासपोर्ट पर एक लिंक के रूप में दिखाया जाता है।',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'डिजिटल उत्पाद पासपोर्ट',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'सामग्री संरचना',
        'dpp_substances_of_concern'     => 'चिंताजनक पदार्थ',
        'dpp_recycled_content_pct'      => 'पुनर्चक्रित सामग्री (%)',
        'dpp_carbon_footprint'          => 'कार्बन फुटप्रिंट',
        'dpp_energy_consumption'        => 'ऊर्जा खपत',
        'dpp_durability_statement'      => 'स्थायित्व विवरण',
        'dpp_repairability_score'       => 'मरम्मत योग्यता स्कोर',
        'dpp_spare_parts_availability'  => 'स्पेयर पार्ट्स की उपलब्धता',
        'dpp_care_instructions'         => 'देखभाल निर्देश',
        'dpp_disassembly_guide'         => 'विघटन मार्गदर्शिका',
        'dpp_manufacturer_name'         => 'निर्माता का नाम',
        'dpp_manufacturing_site'        => 'निर्माण स्थल',
        'dpp_country_of_origin'         => 'उत्पत्ति का देश',
        'dpp_supply_chain_notes'        => 'आपूर्ति श्रृंखला नोट्स',
        'dpp_end_of_life_instructions'  => 'जीवन-अंत निर्देश',
        'dpp_take_back_scheme'          => 'वापसी योजना',
        'dpp_declaration_of_conformity' => 'अनुरूपता घोषणा',
        'dpp_test_reports'              => 'परीक्षण रिपोर्ट',
        'dpp_certificates'              => 'प्रमाणपत्र',
        'dpp_gtin'                      => 'जीटीआईएन',
        'dpp_model_identifier'          => 'मॉडल पहचानकर्ता',
        'dpp_batch_identifier'          => 'बैच पहचानकर्ता',
        'dpp_warranty_terms'            => 'वारंटी शर्तें',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'डिजिटल उत्पाद पासपोर्ट विशेषताएँ सफलतापूर्वक स्थापित की गईं।',
        ],
    ],

    'public' => [
        'badge'         => 'EU डिजिटल उत्पाद पासपोर्ट',
        'search-locale' => 'खोज भाषा',
        'sections'      => [
            'passport' => 'उत्पाद पासपोर्ट',
        ],
        'title'      => 'डिजिटल उत्पाद पासपोर्ट',
        'identifier' => [
            'title'        => 'पहचान',
            'gtin'         => 'GTIN',
            'model'        => 'मॉडल',
            'batch'        => 'बैच',
            'not-provided' => 'उपलब्ध नहीं',
        ],
        'operator' => [
            'title' => 'आर्थिक ऑपरेटर',
        ],
        'documents' => [
            'title' => 'दस्तावेज़',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'पासपोर्ट प्रकाशन वर्तमान में अक्षम है। मौजूदा पासपोर्ट प्रबंधन (देखने और वापस लेने) के लिए नीचे दिखाए गए हैं।',
            'title'           => 'डिजिटल उत्पाद पासपोर्ट',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'चैनल',
            'status'          => 'स्थिति',
            'live-locales'    => 'सक्रिय भाषाएँ',
            'last-published'  => 'अंतिम प्रकाशन',
            'withdraw'        => 'वापस लें',
        ],
        'publish-queued' => 'पासपोर्ट प्रकाशन कतारबद्ध कर दिया गया है।',
        'withdrawn'      => 'पासपोर्ट सफलतापूर्वक वापस ले लिया गया।',
        'mass-publish'   => [
            'action' => 'डिजिटल उत्पाद पासपोर्ट प्रकाशित करें',
            'queued' => 'पासपोर्ट प्रकाशन :count उत्पाद के लिए कतारबद्ध किया गया।',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'पासपोर्ट',
            'view'     => 'देखें',
            'publish'  => 'प्रकाशित करें',
            'withdraw' => 'वापस लें',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'पासपोर्ट',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'प्रकाशित किया जा रहा है…',
                    'queued'              => 'कतार में',
                    'title'               => 'डिजिटल उत्पाद पासपोर्ट',
                    'publishing-disabled' => 'इस चैनल के लिए पासपोर्ट प्रकाशन अक्षम है।',
                    'locale'              => 'भाषा',
                    'version'             => 'संस्करण',
                    'published-at'        => 'प्रकाशित तिथि',
                    'missing-fields'      => 'लापता फ़ील्ड',
                    'not-published'       => 'अप्रकाशित',
                    'unscored'            => 'अमूल्यांकित',
                    'publish'             => 'प्रकाशित करें',
                    'republish'           => 'पुनः प्रकाशित करें',
                    'publish-all'         => 'सभी भाषाएँ प्रकाशित करें',
                    'auto-publish-on'     => 'स्वतः-प्रकाशन चालू है — उत्पाद सहेजे जाने और पूर्णता सीमा पूरी करने पर पासपोर्ट स्वचालित रूप से प्रकाशित होते हैं। अभी प्रकाशित करने के लिए बटनों का उपयोग करें।',
                    'auto-publish-off'    => 'मैनुअल प्रकाशन — प्रत्येक भाषा के लिए इस उत्पाद का पासपोर्ट प्रकाशित करने के लिए बटनों का उपयोग करें।',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute एक मान्य GTIN होना चाहिए (सही चेक अंक के साथ 8, 12, 13 या 14 अंक).',
    ],
    'mapping' => [
        'title' => 'पासपोर्ट फ़ील्ड मैपिंग',
        'info' => 'प्रत्येक पासपोर्ट फ़ील्ड को उस एट्रिब्यूट से लें जिसे आप पहले से बनाए रखते हैं। किसी फ़ील्ड को अनमैप्ड छोड़ दें ताकि उसका समर्पित पासपोर्ट एट्रिब्यूट उपयोग हो।',
        'menu' => 'फ़ील्ड मैपिंग',
        'field' => 'पासपोर्ट फ़ील्ड',
        'source' => 'स्रोत एट्रिब्यूट',
        'select-source' => 'पासपोर्ट एट्रिब्यूट का उपयोग करें',
        'save-btn' => 'मैपिंग सहेजें',
        'type-mismatch' => 'चयनित स्रोत इस पासपोर्ट फ़ील्ड के प्रकार के अनुकूल नहीं है।',
        'saved' => 'फ़ील्ड मैपिंग सफलतापूर्वक सहेजी गई।',
    ],

];
