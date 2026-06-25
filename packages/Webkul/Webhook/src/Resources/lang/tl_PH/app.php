<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Mga Webhook',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Mangyaring i-enable ang Webhook mula sa mga setting',
        'success'       => 'Matagumpay na naipadala ang datos ng produkto sa Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Mga Setting',
            'update' => 'I-update ang mga setting',
        ],
        'logs' => [
            'index'       => 'Mga Log',
            'view'        => 'View',
            'delete'      => 'Burahin',
            'mass-delete' => 'Maramihang pagbura',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Mga Setting',
                    'title'   => 'Mga Setting ng Webhook',
                    'save'    => 'I-save',
                    'general' => 'Pangkalahatan',
                    'active'  => [
                        'label' => 'Aktibong Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'URL ng Webhook',
                    ],
                    'success'    => 'Matagumpay na na-save ang mga setting ng Webhook',
                    'logs-title' => 'Mga Log',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Petsa/Oras',
                        'user'       => 'Gumagamit',
                        'status'     => 'Katayuan',
                        'success'    => 'Tagumpay',
                        'failed'     => 'Nabigo',
                        'delete'     => 'Burahin',
                        'view'       => 'View',
                    ],
                    'title'          => 'Mga Log ng Webhook',
                    'show-title'     => 'Mga Detalye ng Webhook Log',
                    'sent-payload'   => 'Ipinadaling Payload',
                    'response'       => 'Tugon',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Walang payload na naitala para sa log na ito.',
                    'delete-success' => 'Matagumpay na nabura ang mga log ng Webhook',
                    'delete-failed'  => 'Hindi inaasahang nabigo ang pagbura ng mga log ng Webhook',
                ],
            ],
        ],
    ],
];
