<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhooks',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Vui lòng bật Webhook từ cài đặt',
        'success'       => 'Dữ liệu sản phẩm đã được gửi đến Webhook thành công',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Cài đặt',
            'update' => 'Cập nhật cài đặt',
        ],
        'logs' => [
            'index'       => 'Nhật ký',
            'view'        => 'View',
            'delete'      => 'Xóa',
            'mass-delete' => 'Xóa hàng loạt',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Cài đặt',
                    'title'   => 'Cài đặt Webhook',
                    'save'    => 'Lưu',
                    'general' => 'Chung',
                    'active'  => [
                        'label' => 'Webhook đang hoạt động',
                    ],
                    'webhook_url' => [
                        'label' => 'URL Webhook',
                    ],
                    'success'    => 'Cài đặt Webhook đã được lưu thành công',
                    'logs-title' => 'Nhật ký',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Ngày/Giờ',
                        'user'       => 'Người dùng',
                        'status'     => 'Trạng thái',
                        'success'    => 'Thành công',
                        'failed'     => 'Thất bại',
                        'delete'     => 'Xóa',
                        'view'       => 'View',
                    ],
                    'title'          => 'Nhật ký Webhook',
                    'show-title'     => 'Chi tiết nhật ký Webhook',
                    'sent-payload'   => 'Payload đã gửi',
                    'response'       => 'Phản hồi',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Không có payload nào được ghi lại cho nhật ký này.',
                    'delete-success' => 'Nhật ký Webhook đã được xóa thành công',
                    'delete-failed'  => 'Xóa nhật ký Webhook thất bại một cách bất ngờ',
                ],
            ],
        ],
    ],
];
