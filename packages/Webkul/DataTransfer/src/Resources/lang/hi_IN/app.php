<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'उत्पादों',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'यूआरएल कुंजी: \'%s\' SKU: \'%s\' वाले आइटम के लिए पहले से ही जेनरेट किया गया था।',
                    'invalid-attribute-family'                 => 'विशेषता परिवार कॉलम के लिए अमान्य मान (विशेषता परिवार मौजूद नहीं है?)',
                    'invalid-type'                             => 'उत्पाद प्रकार अमान्य है या समर्थित नहीं है',
                    'sku-not-found'                            => 'निर्दिष्ट SKU वाला उत्पाद नहीं मिला',
                    'super-attribute-not-found'                => 'कोड के साथ कॉन्फ़िगर करने योग्य विशेषता :code नहीं मिला या विशेषता परिवार :familyCode से संबंधित नहीं है',
                    'configurable-attributes-not-found'        => 'उत्पाद मॉडल बनाने के लिए कॉन्फ़िगर करने योग्य विशेषताओं की आवश्यकता होती है',
                    'configurable-attributes-wrong-type'       => 'केवल चुनिंदा प्रकार की विशेषताएँ जो स्थानीय या चैनल आधारित नहीं हैं, उन्हें कॉन्फ़िगर करने योग्य उत्पाद के लिए कॉन्फ़िगर करने योग्य विशेषताएँ होने की अनुमति है',
                    'variant-configurable-attribute-not-found' => 'वैरिएंट कॉन्फ़िगर करने योग्य विशेषता :code बनाने के लिए आवश्यक है',
                    'not-unique-variant-product'               => 'समान कॉन्फ़िगर करने योग्य विशेषताओं वाला एक उत्पाद पहले से मौजूद है।',
                    'channel-not-exist'                        => 'यह चैनल मौजूद नहीं है.',
                    'locale-not-in-channel'                    => 'यह स्थान चैनल में चयनित नहीं है.',
                    'locale-not-exist'                         => 'यह स्थान मौजूद नहीं है',
                    'not-unique-value'                         => ':code मान अद्वितीय होना चाहिए।',
                    'incorrect-family-for-variant'             => 'परिवार मूल परिवार के समान ही होना चाहिए',
                    'parent-not-exist'                         => 'अभिभावक मौजूद नहीं है.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'श्रेणियाँ',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'आप किसी चैनल से संबद्ध रूट श्रेणी को नहीं हटा सकते',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'भाषाएँ',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'भाषा कोड \'%s\' इस बैच में पहले ही आयात किया जा चुका है।',
                    'code-not-found-to-delete'    => 'कोड \'%s\' वाली भाषा सिस्टम में नहीं मिली।',
                    'invalid-status'              => 'स्थिति 0 या 1 होनी चाहिए (या डिफ़ॉल्ट सक्षम के लिए खाली)।',
                    'channel-related-locale-root' => 'आप कोड :code वाली भाषा को हटा नहीं सकते क्योंकि यह एक चैनल से जुड़ी है।',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'चैनल',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'कोड :code वाला चैनल हटाने के लिए नहीं मिला।',
                    'locale-not-found'         => 'एक या अधिक भाषाएँ मौजूद नहीं हैं।',
                    'root-category-not-found'  => 'मूल श्रेणी मौजूद नहीं है।',
                    'currency-not-found'       => 'एक या अधिक मुद्राएँ मौजूद नहीं हैं।',
                    'invalid-locale'           => 'यह भाषा मौजूद नहीं है।',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'उत्पादों',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL कुंजी: \'%s\' SKU: \'%s\' वाले आइटम के लिए पहले से ही जेनरेट किया गया था।',
                    'invalid-attribute-family'  => 'विशेषता परिवार कॉलम के लिए अमान्य मान (विशेषता परिवार मौजूद नहीं है?)',
                    'invalid-type'              => 'उत्पाद प्रकार अमान्य है या समर्थित नहीं है',
                    'sku-not-found'             => 'निर्दिष्ट SKU वाला उत्पाद नहीं मिला',
                    'super-attribute-not-found' => 'कोड के साथ सुपर विशेषता: \'%s\' नहीं मिला या विशेषता परिवार से संबंधित नहीं है: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'श्रेणियाँ',
        ],
        'locales' => [
            'title' => 'भाषाएँ',
        ],
        'channels' => [
            'title' => 'चैनल',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'कॉलम संख्या "%s" में खाली हेडर हैं।',
            'column-name-invalid'  => 'अमान्य स्तंभ नाम: "%s"।',
            'column-not-found'     => 'आवश्यक कॉलम नहीं मिले: %s.',
            'column-numbers'       => 'स्तंभों की संख्या शीर्षलेख में पंक्तियों की संख्या से मेल नहीं खाती.',
            'invalid-attribute'    => 'शीर्षलेख में अमान्य विशेषताएँ शामिल हैं: "%s"।',
            'system'               => 'एक अप्रत्याशित सिस्टम त्रुटि उत्पन्न हुई.',
            'wrong-quotes'         => 'सीधे उद्धरणों के स्थान पर घुंघराले उद्धरणों का प्रयोग किया गया।',
            'file-empty'           => 'फ़ाइल खाली है या इसमें शीर्षक पंक्ति नहीं है। कृपया डेटा वाली एक वैध फ़ाइल अपलोड करें।',
        ],
    ],
    'job' => [
        'started'   => 'कार्य निष्पादन प्रारंभ हुआ',
        'completed' => 'कार्य निष्पादन पूरा हुआ',
    ],
];
