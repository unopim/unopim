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
            'index'  => 'Webhook',
            'create' => 'Lumikha',
            'edit'   => 'I-edit',
            'delete' => 'Burahin',
        ],
        'settings' => [
            'index'  => 'Mga Setting',
            'update' => 'I-update ang mga setting',
        ],
        'logs' => [
            'index'       => 'Mga Log',
            'view'        => 'Tingnan',
            'delete'      => 'Burahin',
            'mass-delete' => 'Maramihang pagbura',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Nalikha ang Produkto',
            'updated' => 'Na-update ang Produkto',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Mga Webhook',
            'create-btn'   => 'Lumikha ng Webhook',
            'logs-btn'     => 'Mga Log',
            'back-btn'     => 'Bumalik sa Mga Webhook',
            'default-name' => 'Default',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Pangalan',
                'url'        => 'URL',
                'events'     => 'Mga Kaganapan',
                'status'     => 'Katayuan',
                'active'     => 'Aktibo',
                'inactive'   => 'Hindi aktibo',
                'created_at' => 'Nalikha Noon',
                'edit'       => 'I-edit',
                'delete'     => 'Burahin',
            ],
        ],
        'create' => [
            'title'    => 'Lumikha ng Webhook',
            'cancel'   => 'Kanselahin',
            'save-btn' => 'I-save',
        ],
        'edit' => [
            'title'    => 'I-edit ang Webhook',
            'cancel'   => 'Kanselahin',
            'save-btn' => 'I-save',
        ],
        'form' => [
            'general'       => 'Pangkalahatan',
            'name'          => 'Pangalan',
            'url'           => 'URL',
            'events'        => 'Mga Kaganapan',
            'select-events' => 'Pumili ng mga kaganapan',
            'secret'        => 'Lihim na Panglagda',
            'secret-set'    => 'May naitakda nang lihim',
            'secret-hint'   => 'Ginagamit upang lagdaan ang bawat payload gamit ang HMAC SHA-256 na lagda. Iwanang blangko upang panatilihin ang kasalukuyang lihim.',
            'settings'      => 'Mga Setting',
            'active'        => 'Aktibo',
            'test'          => 'Subukan ang Koneksyon',
            'test-hint'     => 'Magpadala ng test request sa URL sa itaas.',
            'test-btn'      => 'Magpadala ng Test',
            'test-no-url'   => 'Mangyaring maglagay muna ng URL.',
            'test-failed'   => 'Nabigo ang test request.',
            'headers'       => 'Mga Custom na Header',
            'add-header'    => 'Magdagdag ng Header',
            'no-headers'    => 'Walang naidagdag na custom na header.',
            'header-key'    => 'Header',
            'header-value'  => 'Halaga',
        ],
        'create-success' => 'Matagumpay na nalikha ang Webhook',
        'update-success' => 'Matagumpay na na-update ang Webhook',
        'delete-success' => 'Matagumpay na nabura ang Webhook',
        'delete-failed'  => 'Nabigo ang pagbura ng Webhook',
        'validation'     => [
            'unsafe-url' => 'Ang URL ay tumuturo sa pribado, loopback, o panloob na address at hindi pinapayagan.',
            'scheme'     => 'Ang URL ay dapat magsimula sa http:// o https://.',
        ],
        'test' => [
            'payload-message'   => 'Test request ng Unopim webhook',
            'connection-failed' => 'Hindi maabot ang URL. Pakisuri ang URL.',
            'unreachable'       => 'Hindi maabot ang URL (HTTP :code).',
            'reachable'         => 'Naaabot ang URL.',
        ],
        'prune' => [
            'disabled' => 'Naka-disable ang pagpapanatili ng webhook log; walang binura.',
            'done'     => 'Nabura ang :count na webhook log na mas luma sa :days na araw.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Mga Setting',
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
                        'unsafe'            => 'Ang Webhook URL ay tumuturo sa pribado, loopback, o panloob na address at hindi pinapayagan.',
                    ],
                    'success'    => 'Matagumpay na na-save ang mga setting ng Webhook',
                    'title'      => 'Mga Setting ng Webhook',
                    'logs-title' => 'Mga Log',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Kaganapan',
                        'created_at'       => 'Petsa/Oras',
                        'user'             => 'Gumagamit',
                        'status'           => 'Katayuan',
                        'success'          => 'Tagumpay',
                        'failed'           => 'Nabigo',
                        'server_error'     => 'Error ng Server',
                        'timeout_or_error' => 'Timeout/Error',
                        'delete'           => 'Burahin',
                        'view'             => 'Tingnan',
                    ],
                    'title'          => 'Mga Log ng Webhook',
                    'show-title'     => 'Mga Detalye ng Webhook Log',
                    'sent-payload'   => 'Ipinadaling Payload',
                    'response'       => 'Tugon',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Walang payload na naitala para sa log na ito.',
                    'load-failed'    => 'Nabigong i-load ang mga detalye ng log.',
                    'delete-success' => 'Matagumpay na nabura ang mga log ng Webhook',
                    'delete-failed'  => 'Hindi inaasahang nabigo ang pagbura ng mga log ng Webhook',
                    'unauthorized'   => 'Ang pagkilos na ito ay hindi awtorisado',
                ],
            ],
        ],
    ],
];
