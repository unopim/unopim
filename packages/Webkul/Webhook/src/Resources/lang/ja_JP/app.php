<?php

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
            'index'  => 'ウェブフック',
            'create' => '作成',
            'edit'   => '編集',
            'delete' => '削除',
        ],
        'settings' => [
            'index'  => '設定',
            'update' => '設定を更新',
        ],
        'logs' => [
            'index'       => 'ログ',
            'view'        => '表示',
            'delete'      => '削除',
            'mass-delete' => '一括削除',
        ],
    ],

    'events' => [
        'product' => [
            'created' => '製品が作成されました',
            'updated' => '製品が更新されました',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'ウェブフック',
            'create-btn'   => 'Webhookを作成',
            'logs-btn'     => 'ログ',
            'back-btn'     => 'Webhookに戻る',
            'default-name' => 'デフォルト',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => '名前',
                'url'        => 'URL',
                'events'     => 'イベント',
                'status'     => 'ステータス',
                'active'     => '有効',
                'inactive'   => '無効',
                'created_at' => '作成日時',
                'edit'       => '編集',
                'delete'     => '削除',
            ],
        ],
        'create' => [
            'title'    => 'Webhookを作成',
            'cancel'   => 'キャンセル',
            'save-btn' => '保存',
        ],
        'edit' => [
            'title'    => 'Webhookを編集',
            'cancel'   => 'キャンセル',
            'save-btn' => '保存',
        ],
        'form' => [
            'general'       => '一般',
            'name'          => '名前',
            'url'           => 'URL',
            'events'        => 'イベント',
            'select-events' => 'イベントを選択',
            'secret'        => '署名シークレット',
            'secret-set'    => 'シークレットはすでに設定されています',
            'secret-hint'   => '各ペイロードをHMAC SHA-256署名で署名するために使用されます。現在のシークレットを保持するには空白のままにしてください。',
            'settings'      => '設定',
            'active'        => '有効',
            'test'          => '接続テスト',
            'test-hint'     => '上記のURLにテストリクエストを送信します。',
            'test-btn'      => 'テストを送信',
            'test-no-url'   => '最初にURLを入力してください。',
            'test-failed'   => 'テストリクエストが失敗しました。',
            'headers'       => 'カスタムヘッダー',
            'add-header'    => 'ヘッダーを追加',
            'no-headers'    => 'カスタムヘッダーが追加されていません。',
            'header-key'    => 'ヘッダー',
            'header-value'  => '値',
        ],
        'create-success' => 'Webhookが正常に作成されました',
        'update-success' => 'Webhookが正常に更新されました',
        'delete-success' => 'Webhookが正常に削除されました',
        'delete-failed'  => 'Webhookの削除に失敗しました',
        'validation'     => [
            'unsafe-url' => 'URLがプライベート、ループバック、または内部アドレスを指しているため許可されていません。',
            'scheme'     => 'URLはhttp://またはhttps://で始まる必要があります。',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhookテストリクエスト',
            'connection-failed' => 'URLに到達できませんでした。URLを確認してください。',
            'unreachable'       => 'URLに到達できません (HTTP :code)。',
            'reachable'         => 'URLに到達できます。',
        ],
        'prune' => [
            'disabled' => 'Webhookログの保持が無効になっているため、何も削除されませんでした。',
            'done'     => ':days日より古い:count件のWebhookログを削除しました。',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => '設定',
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
                        'unsafe'            => 'Webhook URL がプライベート、ループバック、または内部アドレスを指しているため許可されていません。',
                    ],
                    'success'    => 'Webhook設定が正常に保存されました',
                    'title'      => 'Webhook設定',
                    'logs-title' => 'ログ',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'イベント',
                        'created_at'       => '日時',
                        'user'             => 'ユーザー',
                        'status'           => 'ステータス',
                        'success'          => '成功',
                        'failed'           => '失敗',
                        'server_error'     => 'サーバーエラー',
                        'timeout_or_error' => 'タイムアウト/エラー',
                        'delete'           => '削除',
                        'view'             => '表示',
                    ],
                    'title'          => 'Webhookログ',
                    'show-title'     => 'Webhookログの詳細',
                    'sent-payload'   => '送信済みペイロード',
                    'response'       => 'レスポンス',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'このログにはペイロードが記録されていません。',
                    'load-failed'    => 'ログの詳細の読み込みに失敗しました。',
                    'delete-success' => 'Webhookログが正常に削除されました',
                    'delete-failed'  => 'Webhookログの削除が予期せず失敗しました',
                    'unauthorized'   => 'この操作は許可されていません',
                ],
            ],
        ],
    ],
];
