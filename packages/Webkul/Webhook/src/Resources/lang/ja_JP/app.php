<?php

declare(strict_types=1);

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'ウェブフック',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => '設定からWebhookを有効にしてください',
        'success'       => '製品データがWebhookに正常に送信されました',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'ウェブフック',
        ],
        'settings' => [
            'index'  => '設定',
            'update' => '設定を更新',
        ],
        'logs' => [
            'index'       => 'ログ',
            'delete'      => '削除',
            'mass-delete' => '一括削除',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => '設定',
                    'title'   => 'Webhook設定',
                    'save'    => '保存',
                    'general' => '一般',
                    'active'  => [
                        'label' => '有効なWebhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'ウェブフック URL',
                        'required'          => 'Webhook が有効な場合、Webhook URL は必須です。',
                        'scheme'            => 'Webhook URL は http:// または https:// で始まる必要があります。',
                        'connection_failed' => 'Webhook URL に到達できませんでした。URL を確認してください。',
                        'unreachable'       => 'Webhook URL が無効です (HTTP :code)。',
                    ],
                    'success'    => 'Webhook設定が正常に保存されました',
                    'logs-title' => 'ログ',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => '日時',
                        'user'             => 'ユーザー',
                        'status'           => 'ステータス',
                        'success'          => '成功',
                        'failed'           => '失敗',
                        'server_error'     => 'サーバーエラー',
                        'timeout_or_error' => 'タイムアウト/エラー',
                        'delete'           => '削除',
                    ],
                    'title'          => 'Webhookログ',
                    'delete-success' => 'Webhookログが正常に削除されました',
                    'delete-failed'  => 'Webhookログの削除が予期せず失敗しました',
                ],
            ],
        ],
    ],
];
