<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => '網路鉤子',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => '請在設定中啟用 Webhook',
        'success'       => '產品資料已成功傳送至 Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => '網路鉤子',
        ],
        'settings' => [
            'index'  => '設定',
            'update' => '更新設定',
        ],
        'logs' => [
            'index'       => '日誌',
            'delete'      => '刪除',
            'mass-delete' => '批次刪除',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => '設定',
                    'title'   => 'Webhook 設定',
                    'save'    => '儲存',
                    'general' => '一般',
                    'active'  => [
                        'label' => '啟用 Webhook',
                    ],
                    'webhook_url' => [
                        'label' => '網路鉤子 URL',
                    ],
                    'success'    => 'Webhook 設定已成功儲存',
                    'logs-title' => '日誌',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => '日期/時間',
                        'user'       => '使用者',
                        'status'     => '狀態',
                        'success'    => '成功',
                        'failed'     => '失敗',
                        'delete'     => '刪除',
                    ],
                    'title'          => 'Webhook 日誌',
                    'delete-success' => 'Webhook 日誌已成功刪除',
                    'delete-failed'  => 'Webhook 日誌刪除意外失敗',
                ],
            ],
        ],
    ],
];
