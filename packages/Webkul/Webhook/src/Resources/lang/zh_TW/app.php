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
            'index'  => '網路鉤子',
            'create' => '建立',
            'edit'   => '編輯',
            'delete' => '刪除',
        ],
        'logs' => [
            'index'       => '日誌',
            'view'        => '查看',
            'delete'      => '刪除',
            'mass-delete' => '批次刪除',
        ],
    ],

    'events' => [
        'product' => [
            'created' => '產品已建立',
            'updated' => '產品已更新',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => '網路鉤子',
            'create-btn'   => '建立 Webhook',
            'logs-btn'     => '日誌',
            'back-btn'     => '返回 Webhook',
            'default-name' => '預設',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => '名稱',
                'url'        => 'URL',
                'events'     => '事件',
                'status'     => '狀態',
                'active'     => '已啟用',
                'inactive'   => '未啟用',
                'created_at' => '建立時間',
                'edit'       => '編輯',
                'delete'     => '刪除',
            ],
        ],
        'create' => [
            'title'    => '建立 Webhook',
            'save-btn' => '儲存',
        ],
        'edit' => [
            'title'    => '編輯 Webhook',
            'save-btn' => '儲存',
        ],
        'form' => [
            'general'       => '一般',
            'name'          => '名稱',
            'url'           => 'URL',
            'events'        => '事件',
            'select-events' => '選擇事件',
            'secret'        => '簽署密鑰',
            'secret-set'    => '已設定密鑰',
            'secret-hint'   => '用於使用 HMAC SHA-256 簽章對每個負載進行簽署。留空以保留目前的密鑰。',
            'settings'      => '設定',
            'active'        => '已啟用',
            'test'          => '測試連線',
            'test-hint'     => '向上方的 URL 傳送測試請求。',
            'test-btn'      => '傳送測試',
            'test-no-url'   => '請先輸入 URL。',
            'test-failed'   => '測試請求失敗。',
            'headers'       => '自訂標頭',
            'add-header'    => '新增標頭',
            'no-headers'    => '未新增自訂標頭。',
            'header-key'    => '標頭',
            'header-value'  => '值',
        ],
        'create-success' => 'Webhook 建立成功',
        'update-success' => 'Webhook 更新成功',
        'delete-success' => 'Webhook 刪除成功',
        'delete-failed'  => 'Webhook 刪除失敗',
        'validation'     => [
            'unsafe-url' => '該 URL 指向私有、回環或內部地址，不被允許。',
            'scheme'     => 'URL 必須以 http:// 或 https:// 開頭。',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook 測試請求',
            'connection-failed' => '無法存取該 URL。請檢查 URL。',
            'unreachable'       => '無法存取該 URL (HTTP :code)。',
            'reachable'         => '該 URL 可以存取。',
        ],
        'prune' => [
            'disabled' => 'Webhook 日誌保留已停用；未清理任何內容。',
            'done'     => '已清理 :count 筆超過 :days 天的 Webhook 日誌。',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => '事件',
                        'created_at'       => '日期/時間',
                        'user'             => '使用者',
                        'status'           => '狀態',
                        'success'          => '成功',
                        'failed'           => '失敗',
                        'server_error'     => '伺服器錯誤',
                        'timeout_or_error' => '逾時/錯誤',
                        'delete'           => '刪除',
                        'view'             => '查看',
                    ],
                    'title'          => 'Webhook 日誌',
                    'show-title'     => 'Webhook 日誌詳情',
                    'sent-payload'   => '已傳送的資料',
                    'response'       => '回應',
                    'back'           => 'Back to Logs',
                    'no-payload'     => '此日誌未記錄任何資料。',
                    'load-failed'    => '載入日誌詳情失敗。',
                    'delete-success' => 'Webhook 日誌已成功刪除',
                    'delete-failed'  => 'Webhook 日誌刪除意外失敗',
                    'unauthorized'   => '此操作未經授權',
                ],
            ],
        ],
    ],
];
