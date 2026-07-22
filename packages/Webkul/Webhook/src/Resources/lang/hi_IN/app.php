<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'वेबहुक्स',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'कृपया सेटिंग्स से Webhook सक्षम करें',
        'success'       => 'उत्पाद डेटा Webhook पर सफलतापूर्वक भेजा गया',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'वेबहुक',
            'create' => 'बनाएं',
            'edit'   => 'संपादित करें',
            'delete' => 'हटाएं',
        ],
        'settings' => [
            'index'  => 'सेटिंग्स',
            'update' => 'सेटिंग्स अपडेट करें',
        ],
        'logs' => [
            'index'       => 'लॉग',
            'view'        => 'देखें',
            'delete'      => 'हटाएं',
            'mass-delete' => 'सामूहिक हटाएं',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'उत्पाद बनाया गया',
            'updated' => 'उत्पाद अपडेट किया गया',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'वेबहुक्स',
            'create-btn'   => 'वेबहुक बनाएं',
            'logs-btn'     => 'लॉग',
            'back-btn'     => 'वेबहुक्स पर वापस जाएं',
            'default-name' => 'डिफ़ॉल्ट',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'नाम',
                'url'        => 'URL',
                'events'     => 'इवेंट',
                'status'     => 'स्थिति',
                'active'     => 'सक्रिय',
                'inactive'   => 'निष्क्रिय',
                'created_at' => 'बनाया गया',
                'edit'       => 'संपादित करें',
                'delete'     => 'हटाएं',
            ],
        ],
        'create' => [
            'title'    => 'वेबहुक बनाएं',
            'cancel'   => 'रद्द करें',
            'save-btn' => 'सहेजें',
        ],
        'edit' => [
            'title'    => 'वेबहुक संपादित करें',
            'cancel'   => 'रद्द करें',
            'save-btn' => 'सहेजें',
        ],
        'form' => [
            'general'       => 'सामान्य',
            'name'          => 'नाम',
            'url'           => 'URL',
            'events'        => 'इवेंट',
            'select-events' => 'इवेंट चुनें',
            'secret'        => 'साइनिंग सीक्रेट',
            'secret-set'    => 'एक सीक्रेट पहले से सेट है',
            'secret-hint'   => 'प्रत्येक पेलोड को HMAC SHA-256 हस्ताक्षर से साइन करने के लिए उपयोग किया जाता है। वर्तमान सीक्रेट रखने के लिए खाली छोड़ें।',
            'settings'      => 'सेटिंग्स',
            'active'        => 'सक्रिय',
            'test'          => 'कनेक्शन परीक्षण करें',
            'test-hint'     => 'ऊपर दिए गए URL पर एक परीक्षण अनुरोध भेजें।',
            'test-btn'      => 'परीक्षण भेजें',
            'test-no-url'   => 'कृपया पहले एक URL दर्ज करें।',
            'test-failed'   => 'परीक्षण अनुरोध विफल हुआ।',
            'headers'       => 'कस्टम हेडर',
            'add-header'    => 'हेडर जोड़ें',
            'no-headers'    => 'कोई कस्टम हेडर नहीं जोड़ा गया।',
            'header-key'    => 'हेडर',
            'header-value'  => 'मान',
        ],
        'create-success' => 'वेबहुक सफलतापूर्वक बनाया गया',
        'update-success' => 'वेबहुक सफलतापूर्वक अपडेट किया गया',
        'delete-success' => 'वेबहुक सफलतापूर्वक हटाया गया',
        'delete-failed'  => 'वेबहुक हटाना विफल हुआ',
        'validation'     => [
            'unsafe-url' => 'URL एक प्राइवेट, लूपबैक या आंतरिक पते की ओर इशारा करता है और इसकी अनुमति नहीं है।',
            'scheme'     => 'URL http:// या https:// से शुरू होना चाहिए।',
        ],
        'test' => [
            'payload-message'   => 'Unopim वेबहुक परीक्षण अनुरोध',
            'connection-failed' => 'URL तक नहीं पहुँचा जा सका। कृपया URL जाँचें।',
            'unreachable'       => 'URL तक नहीं पहुँचा जा सकता (HTTP :code)।',
            'reachable'         => 'URL तक पहुँचा जा सकता है।',
        ],
        'prune' => [
            'disabled' => 'वेबहुक लॉग प्रतिधारण अक्षम है; कुछ भी नहीं हटाया गया।',
            'done'     => ':days दिन से पुराने :count वेबहुक लॉग हटाए गए।',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'सेटिंग्स',
                    'save'    => 'सहेजें',
                    'general' => 'सामान्य',
                    'active'  => [
                        'label' => 'सक्रिय Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'वेबहुक URL',
                        'required'          => 'जब Webhook सक्रिय हो तो Webhook URL आवश्यक है।',
                        'scheme'            => 'Webhook URL http:// या https:// से शुरू होना चाहिए।',
                        'connection_failed' => 'Webhook URL तक नहीं पहुँचा जा सका। कृपया URL जाँचें।',
                        'unreachable'       => 'Webhook URL मान्य नहीं है (HTTP :code)।',
                        'unsafe'            => 'Webhook URL एक प्राइवेट, लूपबैक या आंतरिक पते की ओर इशारा करता है और इसकी अनुमति नहीं है।',
                    ],
                    'success'    => 'Webhook सेटिंग्स सफलतापूर्वक सहेजी गईं',
                    'title'      => 'Webhook सेटिंग्स',
                    'logs-title' => 'लॉग',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'वेबहुक',
                        'sku'              => 'SKU',
                        'event'            => 'इवेंट',
                        'created_at'       => 'दिनांक/समय',
                        'user'             => 'उपयोगकर्ता',
                        'status'           => 'स्थिति',
                        'success'          => 'सफल',
                        'failed'           => 'विफल',
                        'server_error'     => 'सर्वर त्रुटि',
                        'timeout_or_error' => 'टाइमआउट/त्रुटि',
                        'delete'           => 'हटाएं',
                        'view'             => 'देखें',
                    ],
                    'title'          => 'Webhook लॉग',
                    'show-title'     => 'Webhook लॉग विवरण',
                    'sent-payload'   => 'भेजा गया पेलोड',
                    'response'       => 'प्रतिक्रिया',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'इस लॉग के लिए कोई पेलोड रिकॉर्ड नहीं किया गया।',
                    'load-failed'    => 'लॉग विवरण लोड करने में विफल।',
                    'delete-success' => 'Webhook लॉग सफलतापूर्वक हटाए गए',
                    'delete-failed'  => 'Webhook लॉग हटाना अप्रत्याशित रूप से विफल हुआ',
                    'unauthorized'   => 'यह कार्रवाई अनधिकृत है',
                ],
            ],
        ],
    ],
];
