<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'गलती करना',
            ],

            'attribute-groups'   => [
                'description'       => 'विवरण',
                'general'           => 'सामान्य',
                'inventories'       => 'सूची',
                'meta-description'  => 'मेटा विवरण',
                'price'             => 'कीमत',
                'technical'         => 'तकनीकी',
                'shipping'          => 'शिपिंग',
            ],

            'attributes'         => [
                'brand'                => 'ब्रांड',
                'color'                => 'रंग',
                'cost'                 => 'लागत',
                'description'          => 'विवरण',
                'featured'             => 'प्रदर्शित',
                'guest-checkout'       => 'मेहमान के होटल छोड़ने का समय',
                'height'               => 'ऊंचाई',
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
                'status'               => 'स्थिति',
                'tax-category'         => 'कर श्रेणी',
                'url-key'              => 'यूआरएल कुंजी',
                'visible-individually' => 'व्यक्तिगत रूप से दृश्यमान',
                'weight'               => 'वज़न',
                'width'                => 'चौड़ाई',
            ],

            'attribute-options'  => [
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

        'category'  => [
            'categories' => [
                'description' => 'मूल श्रेणी विवरण',
                'name'        => 'जड़',
            ],

            'category_fields' => [
                'name'        => 'नाम',
                'description' => 'विवरण',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'हमारे बारे में पृष्ठ सामग्री',
                    'title'   => 'हमारे बारे में',
                ],

                'contact-us'       => [
                    'content' => 'हमसे संपर्क करें पृष्ठ सामग्री',
                    'title'   => 'हमसे संपर्क करें',
                ],

                'customer-service' => [
                    'content' => 'ग्राहक सेवा पृष्ठ सामग्री',
                    'title'   => 'ग्राहक सेवा',
                ],

                'payment-policy'   => [
                    'content' => 'भुगतान नीति पृष्ठ सामग्री',
                    'title'   => 'भुगतान की नीति',
                ],

                'privacy-policy'   => [
                    'content' => 'गोपनीयता नीति पृष्ठ सामग्री',
                    'title'   => 'गोपनीयता नीति',
                ],

                'refund-policy'    => [
                    'content' => 'धनवापसी नीति पृष्ठ सामग्री',
                    'title'   => 'भुगतान वापसी की नीति',
                ],

                'return-policy'    => [
                    'content' => 'वापसी नीति पृष्ठ सामग्री',
                    'title'   => 'वापसी नीति',
                ],

                'shipping-policy'  => [
                    'content' => 'शिपिंग नीति पृष्ठ सामग्री',
                    'title'   => 'शिपिंग नीति',
                ],

                'terms-conditions' => [
                    'content' => 'नियम एवं शर्तें पृष्ठ सामग्री',
                    'title'   => 'नियम एवं शर्तें',
                ],

                'terms-of-use'     => [
                    'content' => 'उपयोग की शर्तें पृष्ठ सामग्री',
                    'title'   => 'उपयोग की शर्तें',
                ],

                'whats-new'        => [
                    'content' => 'नया पृष्ठ सामग्री क्या है',
                    'title'   => 'नया क्या है',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
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

        'customer'  => [
            'customer-groups' => [
                'general'   => 'सामान्य',
                'guest'     => 'अतिथि',
                'wholesale' => 'थोक',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'गलती करना',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'सभी प्रोडक्ट',

                    'options' => [
                        'title' => 'सभी प्रोडक्ट',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'सभी को देखें',
                        'description' => 'पेश है हमारे नए बोल्ड कलेक्शन! साहसी डिज़ाइन और जीवंत कथनों के साथ अपनी शैली को उन्नत करें। आकर्षक पैटर्न और बोल्ड रंगों का अन्वेषण करें जो आपकी अलमारी को फिर से परिभाषित करते हैं। असाधारण को अपनाने के लिए तैयार हो जाइए!',
                        'title'       => 'हमारे नए बोल्ड कलेक्शन के लिए तैयार हो जाइए!',
                    ],

                    'name'    => 'बोल्ड कलेक्शन',
                ],

                'categories-collections' => [
                    'name' => 'श्रेणियाँ संग्रह',
                ],

                'featured-collections'   => [
                    'name'    => 'विशेष संग्रह',

                    'options' => [
                        'title' => 'विशेष रुप से प्रदर्शित प्रोडक्टस',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'फ़ुटर लिंक',

                    'options' => [
                        'about-us'         => 'हमारे बारे में',
                        'contact-us'       => 'हमसे संपर्क करें',
                        'customer-service' => 'ग्राहक सेवा',
                        'payment-policy'   => 'भुगतान की नीति',
                        'privacy-policy'   => 'गोपनीयता नीति',
                        'refund-policy'    => 'भुगतान वापसी की नीति',
                        'return-policy'    => 'वापसी नीति',
                        'shipping-policy'  => 'शिपिंग नीति',
                        'terms-conditions' => 'नियम एवं शर्तें',
                        'terms-of-use'     => 'उपयोग की शर्तें',
                        'whats-new'        => 'नया क्या है',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'हमारे संग्रह',
                        'sub-title-2' => 'हमारे संग्रह',
                        'title'       => 'हमारे नए अतिरिक्त के साथ खेल!',
                    ],

                    'name'    => 'गेम कंटेनर',
                ],

                'image-carousel'         => [
                    'name'    => 'छवि हिंडोला',

                    'sliders' => [
                        'title' => 'नए संग्रह के लिए तैयार हो जाइए',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'नये उत्पाद',

                    'options' => [
                        'title' => 'नये उत्पाद',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'अपने पहले ऑर्डर की खरीदारी पर अभी 40% तक की छूट पाएं',
                    ],

                    'name' => 'जानकारी प्रदान करें',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'सभी प्रमुख क्रेडिट कार्ड पर नो कॉस्ट ईएमआई उपलब्ध है',
                        'free-shipping-info'   => 'सभी आदेश पर मुफ्त शिपिंग का आनंद लें',
                        'product-replace-info' => 'आसान उत्पाद प्रतिस्थापन उपलब्ध!',
                        'time-support-info'    => 'चैट और ईमेल के माध्यम से समर्पित 24/7 सहायता',
                    ],

                    'name'        => 'सेवाएँ सामग्री',

                    'title'       => [
                        'emi-available'   => 'ईएमआई उपलब्ध',
                        'free-shipping'   => 'मुफ़्त शिपिंग',
                        'product-replace' => 'उत्पाद बदलें',
                        'time-support'    => '24/7 सहायता',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'हमारे संग्रह',
                        'sub-title-2' => 'हमारे संग्रह',
                        'sub-title-3' => 'हमारे संग्रह',
                        'sub-title-4' => 'हमारे संग्रह',
                        'sub-title-5' => 'हमारे संग्रह',
                        'sub-title-6' => 'हमारे संग्रह',
                        'title'       => 'हमारे नए अतिरिक्त के साथ खेल!',
                    ],

                    'name'    => 'शीर्ष संग्रह',
                ],
            ],
        ],

        'user'      => [
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

            'installation-processing'   => [
                'unopim'            => 'स्थापना यूनोपिम',
                'unopim-info'       => 'डेटाबेस तालिकाएँ बनाने में कुछ क्षण लग सकते हैं',
                'title'             => 'इंस्टालेशन',
            ],

            'installation-completed'    => [
                'admin-panel'                   => 'व्यवस्थापक पैनल',
                'unopim-forums'                 => 'यूनोपिम फोरम',
                'explore-unopim-extensions'     => 'यूनोपिम एक्सटेंशन का अन्वेषण करें',
                'title-info'                    => 'UnoPim आपके सिस्टम पर सफलतापूर्वक इंस्टॉल हो गया है।',
                'title'                         => 'स्थापना पूर्ण हुई',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'डेटाबेस तालिका बनाएं',
                'install-info-button'     => 'के लिए नीचे दिए गए बटन पर क्लिक करें',
                'install-info'            => 'स्थापना के लिए यूनोपिम',
                'install'                 => 'इंस्टालेशन',
                'populate-database-table' => 'डेटाबेस तालिकाएँ आबाद करें',
                'start-installation'      => 'इंस्टालेशन प्रारंभ करें',
                'title'                   => 'इंस्टालेशन के लिए तैयार',
            ],

            'start'                     => [
                'locale'        => 'स्थान',
                'main'          => 'शुरू',
                'select-locale' => 'स्थान का चयन करें',
                'title'         => 'आपका यूनोपिम इंस्टॉल',
                'welcome-title' => 'यूनोपिम में आपका स्वागत है '.core()->version(),
            ],

            'server-requirements'       => [
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

            'arabic'                    => 'अरबी',
            'back'                      => 'पीछे',
            'UnoPim-info'               => 'द्वारा एक सामुदायिक परियोजना',
            'unopim-logo'               => 'यूनोपिम लोगो',
            'unopim'                    => 'यूनोपिम',
            'bengali'                   => 'बंगाली',
            'chinese'                   => 'चीनी',
            'continue'                  => 'जारी रखना',
            'dutch'                     => 'डच',
            'english'                   => 'अंग्रेज़ी',
            'french'                    => 'फ़्रेंच',
            'german'                    => 'जर्मन',
            'hebrew'                    => 'यहूदी',
            'hindi'                     => 'हिंदी',
            'installation-description'  => 'UnoPim इंस्टालेशन में आम तौर पर कई चरण शामिल होते हैं। यहां UnoPim के लिए इंस्टॉलेशन प्रक्रिया की सामान्य रूपरेखा दी गई है:',
            'wizard-language'           => 'संस्थापन विज़ार्ड भाषा',
            'installation-info'         => 'हम आपको यहाँ देखकर प्रसन्न हैं!',
            'installation-title'        => 'इंस्टालेशन में आपका स्वागत है',
            'italian'                   => 'इतालवी',
            'japanese'                  => 'जापानी',
            'persian'                   => 'फ़ारसी',
            'polish'                    => 'पोलिश',
            'portuguese'                => 'ब्राज़ीलियाई पुर्तगाली',
            'russian'                   => 'रूसी',
            'save-configuration'        => 'कॉन्फ़िगरेशन सहेजें',
            'sinhala'                   => 'सिंहली',
            'skip'                      => 'छोडना',
            'spanish'                   => 'स्पैनिश',
            'title'                     => 'यूनोपिम इंस्टॉलर',
            'turkish'                   => 'तुर्की',
            'ukrainian'                 => 'यूक्रेनी',
            'webkul'                    => 'वेबकुल',
        ],
    ],
];
