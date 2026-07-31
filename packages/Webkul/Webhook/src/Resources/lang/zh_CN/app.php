<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => '网络钩子',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => '请在设置中启用 Webhook',
        'success'       => '产品数据已成功发送到 Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index'  => '网络钩子',
            'create' => '创建',
            'edit'   => '编辑',
            'delete' => '删除',
        ],
        'logs' => [
            'index'       => '日志',
            'view'        => '查看',
            'delete'      => '删除',
            'mass-delete' => '批量删除',
        ],
    ],

    'events' => [
        'product' => [
            'created' => '产品已创建',
            'updated' => '产品已更新',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => '网络钩子',
            'create-btn'   => '创建 Webhook',
            'logs-btn'     => '日志',
            'back-btn'     => '返回 Webhook',
            'default-name' => '默认',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => '名称',
                'url'        => 'URL',
                'events'     => '事件',
                'status'     => '状态',
                'active'     => '已激活',
                'inactive'   => '未激活',
                'created_at' => '创建时间',
                'edit'       => '编辑',
                'delete'     => '删除',
            ],
        ],
        'create' => [
            'title'    => '创建 Webhook',
            'save-btn' => '保存',
        ],
        'edit' => [
            'title'    => '编辑 Webhook',
            'save-btn' => '保存',
        ],
        'form' => [
            'general'       => '常规',
            'name'          => '名称',
            'url'           => 'URL',
            'events'        => '事件',
            'select-events' => '选择事件',
            'secret'        => '签名密钥',
            'secret-set'    => '已设置密钥',
            'secret-hint'   => '用于使用 HMAC SHA-256 签名对每个负载进行签名。留空以保留当前密钥。',
            'settings'      => '设置',
            'active'        => '已激活',
            'test'          => '测试连接',
            'test-hint'     => '向上面的 URL 发送测试请求。',
            'test-btn'      => '发送测试',
            'test-no-url'   => '请先输入 URL。',
            'test-failed'   => '测试请求失败。',
            'headers'       => '自定义标头',
            'add-header'    => '添加标头',
            'no-headers'    => '未添加自定义标头。',
            'header-key'    => '标头',
            'header-value'  => '值',
        ],
        'create-success' => 'Webhook 创建成功',
        'update-success' => 'Webhook 更新成功',
        'delete-success' => 'Webhook 删除成功',
        'delete-failed'  => 'Webhook 删除失败',
        'validation'     => [
            'unsafe-url' => '该 URL 指向私有、回环或内部地址，不被允许。',
            'scheme'     => 'URL 必须以 http:// 或 https:// 开头。',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook 测试请求',
            'connection-failed' => '无法访问该 URL。请检查 URL。',
            'unreachable'       => '无法访问该 URL (HTTP :code)。',
            'reachable'         => '该 URL 可以访问。',
        ],
        'prune' => [
            'disabled' => 'Webhook 日志保留已禁用；未清理任何内容。',
            'done'     => '已清理 :count 条超过 :days 天的 Webhook 日志。',
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
                        'created_at'       => '日期/时间',
                        'user'             => '用户',
                        'status'           => '状态',
                        'success'          => '成功',
                        'failed'           => '失败',
                        'server_error'     => '服务器错误',
                        'timeout_or_error' => '超时/错误',
                        'delete'           => '删除',
                        'view'             => '查看',
                    ],
                    'title'          => 'Webhook 日志',
                    'show-title'     => 'Webhook 日志详情',
                    'sent-payload'   => '已发送的数据',
                    'response'       => '响应',
                    'back'           => 'Back to Logs',
                    'no-payload'     => '此日志未记录任何数据。',
                    'load-failed'    => '加载日志详情失败。',
                    'delete-success' => 'Webhook 日志已成功删除',
                    'delete-failed'  => 'Webhook 日志删除意外失败',
                    'unauthorized'   => '此操作未经授权',
                ],
            ],
        ],
    ],
];
