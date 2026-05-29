<?php

declare(strict_types=1);

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
            'index' => 'वेबहुक',
        ],
        'settings' => [
            'index'  => 'सेटिंग्स',
            'update' => 'सेटिंग्स अपडेट करें',
        ],
        'logs' => [
            'index'       => 'लॉग',
            'delete'      => 'हटाएं',
            'mass-delete' => 'सामूहिक हटाएं',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'सेटिंग्स',
                    'title'   => 'Webhook सेटिंग्स',
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
                    'logs-title' => 'लॉग',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'दिनांक/समय',
                        'user'             => 'उपयोगकर्ता',
                        'status'           => 'स्थिति',
                        'success'          => 'सफल',
                        'failed'           => 'विफल',
                        'server_error'     => 'सर्वर त्रुटि',
                        'timeout_or_error' => 'टाइमआउट/त्रुटि',
                        'delete'           => 'हटाएं',
                    ],
                    'title'          => 'Webhook लॉग',
                    'delete-success' => 'Webhook लॉग सफलतापूर्वक हटाए गए',
                    'delete-failed'  => 'Webhook लॉग हटाना अप्रत्याशित रूप से विफल हुआ',
                ],
            ],
        ],
    ],
];
