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
        'category-fields' => [
            'title'      => 'श्रेणी फ़ील्ड',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'श्रेणी फ़ील्ड कोड :code पहले से उपयोग में है।',
                    'code_not_found_to_delete' => 'हटाने के लिए श्रेणी फ़ील्ड कोड नहीं मिला।',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'विशेषताएँ',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'विशेषता कोड :code पहले से ही उपयोग में है।',
                    'code_not_found_to_delete'             => 'हटाने के लिए विशेषता कोड नहीं मिला।',
                    'code_is_system_and_cannot_be_deleted' => 'सिस्टम विशेषता को हटाया नहीं जा सकता।',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'विशेषता समूह',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'विशेषता समूह कोड :code पहले से ही उपयोग में है।',
                    'code_not_found_to_delete'             => 'हटाने के लिए विशेषता समूह कोड नहीं मिला।',
                    'code_is_system_and_cannot_be_deleted' => 'सिस्टम विशेषता समूह को हटाया नहीं जा सकता।',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'विशेषता परिवार',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'विशेषता परिवार कोड :code पहले से ही उपयोग में है।',
                    'code_not_found_to_delete' => 'हटाने के लिए विशेषता परिवार कोड नहीं मिला।',
                    'invalid-attribute-group'  => 'विशेषता समूह ":code" मौजूद नहीं है।',
                    'invalid-attribute'        => 'विशेषता ":code" मौजूद नहीं है।',
                    'invalid-channel'          => 'चैनल ":code" मौजूद नहीं है।',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'विशेषता विकल्प',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'विशेषता विकल्प कोड :code पहले से ही उपयोग में है।',
                    'code_not_found_to_delete' => 'हटाने के लिए विशेषता विकल्प कोड नहीं मिला।',
                    'locale-not-exist'         => 'लोकेल ":code" मौजूद नहीं है।',
                    'invalid-attribute'        => 'विशेषता ":code" मौजूद नहीं है।',
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
            'title'   => 'Currencies',
            'filters' => [
                'status' => 'स्थिति',
                'enable' => 'सक्षम',
                'all'    => 'सभी',
            ],
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
            'title'   => 'Users',
            'filters' => [
                'status' => 'स्थिति',
                'active' => 'सक्रिय',
                'all'    => 'सभी',
            ],
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
        'export-too-large' => 'यह निर्यात चलाने के लिए बहुत बड़ा है: अनुमानित :rows पंक्तियाँ × :columns स्तंभ (~:estimated) उपलब्ध स्थान (~:available) से अधिक हैं। कम चैनल/लोकेल (और विशेषताएँ) चुनकर निर्यात को सीमित करें और पुनः प्रयास करें।',
        'fields'           => [
            'file-format'         => 'फ़ाइल प्रारूप',
            'with-media'          => 'मीडिया के साथ',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'स्थिति',
            'enable'         => 'सक्षम',
            'all'            => 'सभी',
        ],
        'products' => [
            'title'              => 'उत्पादों',
            'invalid-locales'    => 'चयनित सभी लोकेल चयनित चैनलों के लिए उपलब्ध नहीं हैं।',
            'invalid-currencies' => 'चयनित सभी मुद्राएँ चयनित चैनलों के लिए उपलब्ध नहीं हैं।',
            'filters'            => [
                'channels'             => 'चैनल',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'मुद्राएँ',
                'currencies-info'      => 'मूल्य विशेषताएँ प्रत्येक चयनित मुद्रा के लिए निर्यात की जाती हैं। सभी चैनल मुद्राएँ निर्यात करने के लिए खाली छोड़ें।',
                'locales'              => 'लोकेल',
                'locales-info'         => 'स्थानीयकरण योग्य विशेषताएँ प्रत्येक चयनित लोकेल के लिए एक बार निर्यात की जाती हैं। सभी चैनल लोकेल निर्यात करने के लिए खाली छोड़ें।',
                'attributes'           => 'विशेषताएँ',
                'attributes-info'      => 'केवल चयनित विशेषताएँ निर्यात की जाती हैं। परिवार की सभी विशेषताएँ निर्यात करने के लिए खाली छोड़ें।',
                'attribute-families'   => 'विशेषता परिवार',
                'categories'           => 'श्रेणियाँ',
                'completeness'         => 'पूर्णता',
                'completeness-options' => [
                    'none'         => 'पूर्णता पर कोई शर्त नहीं',
                    'at-least-one' => 'कम से कम एक चयनित लोकेल में पूर्ण',
                    'all'          => 'सभी चयनित लोकेल में पूर्ण',
                ],
                'time-condition' => 'समय शर्त',
                'time-options'   => [
                    'none'              => 'कोई दिनांक शर्त नहीं',
                    'last-n-days'       => 'पिछले N दिनों में अपडेट किए गए उत्पाद',
                    'between-dates'     => 'दो तिथियों के बीच अपडेट किए गए उत्पाद',
                    'since-last-export' => 'पिछले निर्यात के बाद से अपडेट किए गए उत्पाद',
                ],
                'time-value'     => 'दिनों की संख्या',
                'time-date'      => 'प्रारंभ तिथि',
                'time-date-end'  => 'समाप्ति तिथि',
                'status'         => 'स्थिति',
                'status-options' => [
                    'enable'  => 'सक्षम',
                    'disable' => 'अक्षम',
                    'all'     => 'सभी',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'पहचानकर्ता',
                'identifiers-info' => 'केवल उन उत्पादों को निर्यात करने के लिए प्रति पंक्ति एक SKU / पहचानकर्ता पेस्ट करें। सभी उत्पादों को निर्यात करने के लिए खाली छोड़ें।',
            ],
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
        'category-fields' => [
            'title' => 'श्रेणी फ़ील्ड',
        ],
        'attributes' => [
            'title' => 'विशेषताएँ',
        ],
        'attribute-groups' => [
            'title' => 'विशेषता समूह',
        ],
        'attribute-families' => [
            'title' => 'विशेषता परिवार',
        ],
        'attribute-options' => [
            'title' => 'विशेषता विकल्प',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'कार्य निष्पादन प्रारंभ हुआ',
        'completed' => 'कार्य निष्पादन पूरा हुआ',
    ],
];
