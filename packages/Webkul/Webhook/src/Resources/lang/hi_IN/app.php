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
            'index' => 'वेबहुक',
        ],
        'settings' => [
            'index'  => 'सेटिंग्स',
            'update' => 'सेटिंग्स अपडेट करें',
        ],
        'logs' => [
            'index'       => 'लॉग',
            'view'        => 'View',
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
                        'label' => 'वेबहुक URL',
                    ],
                    'success'    => 'Webhook सेटिंग्स सफलतापूर्वक सहेजी गईं',
                    'logs-title' => 'लॉग',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'दिनांक/समय',
                        'user'       => 'उपयोगकर्ता',
                        'status'     => 'स्थिति',
                        'success'    => 'सफल',
                        'failed'     => 'विफल',
                        'delete'     => 'हटाएं',
                        'view'       => 'View',
                    ],
                    'title'          => 'Webhook लॉग',
                    'show-title'     => 'Webhook लॉग विवरण',
                    'sent-payload'   => 'भेजा गया पेलोड',
                    'response'       => 'प्रतिक्रिया',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'इस लॉग के लिए कोई पेलोड रिकॉर्ड नहीं किया गया।',
                    'delete-success' => 'Webhook लॉग सफलतापूर्वक हटाए गए',
                    'delete-failed'  => 'Webhook लॉग हटाना अप्रत्याशित रूप से विफल हुआ',
                ],
            ],
        ],
    ],
];
