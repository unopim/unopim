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
            'index' => '网络钩子',
        ],
        'settings' => [
            'index'  => '设置',
            'update' => '更新设置',
        ],
        'logs' => [
            'index'       => '日志',
            'delete'      => '删除',
            'mass-delete' => '批量删除',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => '设置',
                    'title'   => 'Webhook 设置',
                    'save'    => '保存',
                    'general' => '常规',
                    'active'  => [
                        'label' => '启用 Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => '网络钩子 URL',
                        'required'          => '当 Webhook 处于激活状态时，必须提供 Webhook URL。',
                        'scheme'            => 'Webhook URL 必须以 http:// 或 https:// 开头。',
                        'connection_failed' => '无法访问 Webhook URL。请检查 URL。',
                        'unreachable'       => 'Webhook URL 无效 (HTTP :code)。',
                        'unsafe'            => 'Webhook URL 指向私有、回环或内部地址,不被允许。',
                    ],
                    'success'    => 'Webhook 设置已成功保存',
                    'logs-title' => '日志',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => '日期/时间',
                        'user'             => '用户',
                        'status'           => '状态',
                        'success'          => '成功',
                        'failed'           => '失败',
                        'server_error'     => '服务器错误',
                        'timeout_or_error' => '超时/错误',
                        'delete'           => '删除',
                    ],
                    'title'          => 'Webhook 日志',
                    'delete-success' => 'Webhook 日志已成功删除',
                    'delete-failed'  => 'Webhook 日志删除意外失败',
                ],
            ],
        ],
    ],
];
