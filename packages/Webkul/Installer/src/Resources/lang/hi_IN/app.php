<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'गलती करना',
            ],

            'attribute-groups' => [
                'description'      => 'विवरण',
                'general'          => 'सामान्य',
                'meta-description' => 'मेटा विवरण',
                'price'            => 'मूल्य',
                'media'            => 'मीडिया',
            ],

            'attributes' => [
                'brand'                => 'ब्रांड',
                'color'                => 'रंग',
                'cost'                 => 'लागत',
                'description'          => 'विवरण',
                'featured'             => 'प्रदर्शित',
                'guest-checkout'       => 'मेहमान के होटल छोड़ने का समय',
                'height'               => 'ऊंचाई',
                'image'                => 'छवि',
                'length'               => 'लंबाई',
                'manage-stock'         => 'स्टॉक प्रबंधित करें',
                'meta-description'     => 'मेटा विवरण',
                'meta-keywords'        => 'मेटा खोजशब्दों',
                'meta-title'           => 'मेटा शीर्षक',
                'name'                 => 'नाम',
                'new'                  => 'नया',
                'price'                => 'कीमत',
                'product-number'       => 'उत्पाद संख्या',
                'short-description'    => 'संक्षिप्त वर्णन',
                'size'                 => 'आकार',
                'sku'                  => 'एसकेयू',
                'special-price-from'   => 'से विशेष कीमत',
                'special-price-to'     => 'विशेष कीमत के लिए',
                'special-price'        => 'विशेष कीमत',
                'tax-category'         => 'कर श्रेणी',
                'url-key'              => 'यूआरएल कुंजी',
                'visible-individually' => 'व्यक्तिगत रूप से दृश्यमान',
                'weight'               => 'वज़न',
                'width'                => 'चौड़ाई',
            ],

            'attribute-options' => [
                'black'  => 'काला',
                'green'  => 'हरा',
                'l'      => 'एल',
                'm'      => 'एम',
                'red'    => 'लाल',
                's'      => 'एस',
                'white'  => 'सफ़ेद',
                'xl'     => 'एक्स्ट्रा लार्ज',
                'yellow' => 'पीला',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'मूल श्रेणी विवरण',
                'name'        => 'जड़',
            ],

            'category_fields' => [
                'name'        => 'नाम',
                'description' => 'विवरण',
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'डेमो स्टोर',
                'meta-keywords'    => 'डेमो स्टोर मेटा कीवर्ड',
                'meta-description' => 'डेमो स्टोर मेटा विवरण',
                'name'             => 'गलती करना',
            ],

            'currencies' => [
                'AED' => 'दिर्हाम',
                'AFN' => 'इज़राइली शेकेल',
                'CNY' => 'चीनी युवान',
                'EUR' => 'यूरो',
                'GBP' => 'पौंड स्टर्लिंग',
                'INR' => 'भारतीय रुपया',
                'IRR' => 'ईरानी रियाल',
                'JPY' => 'जापानी येन',
                'RUB' => 'रूसी रूबल',
                'SAR' => 'सऊदी रियाल',
                'TRY' => 'तुर्की लीरा',
                'UAH' => 'यूक्रेनी रिव्निया',
                'USD' => 'अमेरिकी डॉलर',
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'इस भूमिका के उपयोगकर्ताओं के पास सभी पहुंच होगी',
                'name'        => 'प्रशासक',
            ],

            'users' => [
                'name' => 'उदाहरण',
            ],
        ],
    ],

    'installer' => [

        'middleware' => [
            'already-installed' => 'एप्लिकेशन पहले से ही इंस्टॉल है।',
        ],

        'index' => [
            'create-administrator' => [
                'admin'            => 'व्यवस्थापक',
                'unopim'           => 'यूनोपिम',
                'confirm-password' => 'पासवर्ड की पुष्टि कीजिये',
                'email-address'    => 'admin@example.com',
                'email'            => 'ईमेल',
                'password'         => 'पासवर्ड',
                'title'            => 'व्यवस्थापक बनाएँ',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'अनुमत मुद्राएँ',
                'allowed-locales'     => 'अनुमत स्थान',
                'application-name'    => 'आवेदन का नाम',
                'unopim'              => 'यूनोपिम',
                'chinese-yuan'        => 'चीनी युआन (CNY)',
                'database-connection' => 'डेटाबेस कनेक्शन',
                'database-hostname'   => 'डेटाबेस होस्टनाम',
                'database-name'       => 'डेटाबेस का नाम',
                'database-password'   => 'डेटाबेस पासवर्ड',
                'database-port'       => 'डेटाबेस पोर्ट',
                'database-prefix'     => 'डेटाबेस उपसर्ग',
                'database-username'   => 'डेटाबेस उपयोगकर्ता नाम',
                'default-currency'    => 'डिफ़ॉल्ट मुद्रा',
                'default-locale'      => 'डिफ़ॉल्ट स्थान',
                'default-timezone'    => 'डिफ़ॉल्ट समयक्षेत्र',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'डिफ़ॉल्ट यूआरएल',
                'dirham'              => 'दिरहम (एईडी)',
                'euro'                => 'यूरो (EUR)',
                'iranian'             => 'ईरानी रियाल (आईआरआर)',
                'israeli'             => 'इज़राइली शेकेल (एएफएन)',
                'japanese-yen'        => 'जापानी येन (जेपीवाई)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'पाउंड स्टर्लिंग (GBP)',
                'rupee'               => 'भारतीय रुपया (INR)',
                'russian-ruble'       => 'रूसी रूबल (आरयूबी)',
                'saudi'               => 'सऊदी रियाल (SAR)',
                'select-timezone'     => 'समयक्षेत्र चुनें',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'डेटाबेस कॉन्फ़िगरेशन',
                'turkish-lira'        => 'तुर्की लीरा (TRY)',
                'ukrainian-hryvnia'   => 'यूक्रेनी रिव्निया (UAH)',
                'usd'                 => 'अमेरिकी डॉलर (USD)',
                'warning-message'     => 'सावधान! आपकी डिफ़ॉल्ट सिस्टम भाषाओं के साथ-साथ डिफ़ॉल्ट मुद्रा की सेटिंग्स स्थायी हैं और इन्हें फिर कभी नहीं बदला जा सकता है।',
            ],

            'installation-processing' => [
                'unopim'      => 'स्थापना यूनोपिम',
                'unopim-info' => 'डेटाबेस तालिकाएँ बनाने में कुछ क्षण लग सकते हैं',
                'title'       => 'इंस्टालेशन',
            ],

            'installation-completed' => [
                'admin-panel'               => 'व्यवस्थापक पैनल',
                'unopim-forums'             => 'यूनोपिम फोरम',
                'explore-unopim-extensions' => 'यूनोपिम एक्सटेंशन का अन्वेषण करें',
                'title-info'                => 'UnoPim आपके सिस्टम पर सफलतापूर्वक इंस्टॉल हो गया है।',
                'title'                     => 'स्थापना पूर्ण हुई',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'डेटाबेस तालिका बनाएं',
                'install-info-button'     => 'के लिए नीचे दिए गए बटन पर क्लिक करें',
                'install-info'            => 'स्थापना के लिए यूनोपिम',
                'install'                 => 'इंस्टालेशन',
                'populate-database-table' => 'डेटाबेस तालिकाएँ आबाद करें',
                'start-installation'      => 'इंस्टालेशन प्रारंभ करें',
                'title'                   => 'इंस्टालेशन के लिए तैयार',
            ],

            'start' => [
                'locale'        => 'स्थान',
                'main'          => 'शुरू',
                'select-locale' => 'स्थान का चयन करें',
                'title'         => 'आपका यूनोपिम इंस्टॉल',
                'welcome-title' => 'यूनोपिम में आपका स्वागत है :version',
            ],

            'server-requirements' => [
                'calendar'    => 'कैलेंडर',
                'ctype'       => 'cप्रकार',
                'curl'        => 'कर्ल',
                'dom'         => 'डोम',
                'fileinfo'    => 'फ़ाइलजानकारी',
                'filter'      => 'फ़िल्टर',
                'gd'          => 'गोलों का अंतर',
                'hash'        => 'हैश',
                'intl'        => 'अंतर्राष्ट्रीय',
                'json'        => 'JSON',
                'mbstring'    => 'एमबीस्ट्रिंग',
                'openssl'     => 'Opensl',
                'pcre'        => 'पीसीआर',
                'pdo'         => 'पीडीओ',
                'php-version' => '8.2 या उच्चतर',
                'php'         => 'पीएचपी',
                'session'     => 'सत्र',
                'title'       => 'सिस्टम आवश्यकताएं',
                'tokenizer'   => 'टोकननाइज़र',
                'xml'         => 'एक्सएमएल',
            ],

            'back'                     => 'पीछे',
            'unopim-info'              => 'द्वारा एक सामुदायिक परियोजना',
            'unopim-logo'              => 'यूनोपिम लोगो',
            'unopim'                   => 'यूनोपिम',
            'continue'                 => 'जारी रखना',
            'installation-description' => 'UnoPim इंस्टालेशन में आम तौर पर कई चरण शामिल होते हैं। यहां UnoPim के लिए इंस्टॉलेशन प्रक्रिया की सामान्य रूपरेखा दी गई है:',
            'wizard-language'          => 'संस्थापन विज़ार्ड भाषा',
            'installation-info'        => 'हम आपको यहाँ देखकर प्रसन्न हैं!',
            'installation-title'       => 'इंस्टालेशन में आपका स्वागत है',
            'save-configuration'       => 'कॉन्फ़िगरेशन सहेजें',
            'skip'                     => 'छोडना',
            'title'                    => 'यूनोपिम इंस्टॉलर',
            'webkul'                   => 'वेबकुल',
        ],
    ],
];
