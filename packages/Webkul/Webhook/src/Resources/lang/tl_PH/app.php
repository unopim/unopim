<?php

declare(strict_types=1);

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
                        'label'             => 'URL ng Webhook',
                        'required'          => 'Kinakailangan ang Webhook URL kapag aktibo ang Webhook.',
                        'scheme'            => 'Ang Webhook URL ay dapat magsimula sa http:// o https://.',
                        'connection_failed' => 'Hindi maabot ang Webhook URL. Pakisuri ang URL.',
                        'unreachable'       => 'Hindi wasto ang Webhook URL (HTTP :code).',
                    ],
                    'success'    => 'Matagumpay na na-save ang mga setting ng Webhook',
                    'logs-title' => 'Mga Log',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Petsa/Oras',
                        'user'             => 'Gumagamit',
                        'status'           => 'Katayuan',
                        'success'          => 'Tagumpay',
                        'failed'           => 'Nabigo',
                        'server_error'     => 'Error ng Server',
                        'timeout_or_error' => 'Timeout/Error',
                        'delete'           => 'Burahin',
                    ],
                    'title'          => 'Mga Log ng Webhook',
                    'delete-success' => 'Matagumpay na nabura ang mga log ng Webhook',
                    'delete-failed'  => 'Hindi inaasahang nabigo ang pagbura ng mga log ng Webhook',
                ],
            ],
        ],
    ],
];
