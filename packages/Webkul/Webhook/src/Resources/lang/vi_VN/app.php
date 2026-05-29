<?php

declare(strict_types=1);

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook',
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
                        'label'             => 'URL Webhook',
                        'required'          => 'URL Webhook là bắt buộc khi Webhook đang hoạt động.',
                        'scheme'            => 'URL Webhook phải bắt đầu bằng http:// hoặc https://.',
                        'connection_failed' => 'Không thể truy cập URL Webhook. Vui lòng kiểm tra URL.',
                        'unreachable'       => 'URL Webhook không hợp lệ (HTTP :code).',
                    ],
                    'success'    => 'Cài đặt Webhook đã được lưu thành công',
                    'logs-title' => 'Nhật ký',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Ngày/Giờ',
                        'user'             => 'Người dùng',
                        'status'           => 'Trạng thái',
                        'success'          => 'Thành công',
                        'failed'           => 'Thất bại',
                        'server_error'     => 'Lỗi máy chủ',
                        'timeout_or_error' => 'Hết thời gian/Lỗi',
                        'delete'           => 'Xóa',
                    ],
                    'title'          => 'Nhật ký Webhook',
                    'delete-success' => 'Nhật ký Webhook đã được xóa thành công',
                    'delete-failed'  => 'Xóa nhật ký Webhook thất bại một cách bất ngờ',
                ],
            ],
        ],
    ],
];
