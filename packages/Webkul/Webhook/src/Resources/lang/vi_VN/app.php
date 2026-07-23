<?php

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
            'index'  => 'Webhook',
            'create' => 'Tạo',
            'edit'   => 'Chỉnh sửa',
            'delete' => 'Xóa',
        ],
        'logs' => [
            'index'       => 'Nhật ký',
            'view'        => 'Xem',
            'delete'      => 'Xóa',
            'mass-delete' => 'Xóa hàng loạt',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Sản phẩm đã được tạo',
            'updated' => 'Sản phẩm đã được cập nhật',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhook',
            'create-btn'   => 'Tạo Webhook',
            'logs-btn'     => 'Nhật ký',
            'back-btn'     => 'Quay lại Webhook',
            'default-name' => 'Mặc định',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Tên',
                'url'        => 'URL',
                'events'     => 'Sự kiện',
                'status'     => 'Trạng thái',
                'active'     => 'Hoạt động',
                'inactive'   => 'Không hoạt động',
                'created_at' => 'Ngày tạo',
                'edit'       => 'Chỉnh sửa',
                'delete'     => 'Xóa',
            ],
        ],
        'create' => [
            'title'    => 'Tạo Webhook',
            'save-btn' => 'Lưu',
        ],
        'edit' => [
            'title'    => 'Chỉnh sửa Webhook',
            'save-btn' => 'Lưu',
        ],
        'form' => [
            'general'       => 'Chung',
            'name'          => 'Tên',
            'url'           => 'URL',
            'events'        => 'Sự kiện',
            'select-events' => 'Chọn sự kiện',
            'secret'        => 'Khóa bí mật ký',
            'secret-set'    => 'Đã thiết lập một khóa bí mật',
            'secret-hint'   => 'Được dùng để ký mỗi payload bằng chữ ký HMAC SHA-256. Để trống để giữ khóa bí mật hiện tại.',
            'settings'      => 'Cài đặt',
            'active'        => 'Hoạt động',
            'test'          => 'Kiểm tra kết nối',
            'test-hint'     => 'Gửi một yêu cầu kiểm tra đến URL ở trên.',
            'test-btn'      => 'Gửi kiểm tra',
            'test-no-url'   => 'Vui lòng nhập URL trước.',
            'test-failed'   => 'Yêu cầu kiểm tra thất bại.',
            'headers'       => 'Tiêu đề tùy chỉnh',
            'add-header'    => 'Thêm tiêu đề',
            'no-headers'    => 'Chưa thêm tiêu đề tùy chỉnh nào.',
            'header-key'    => 'Tiêu đề',
            'header-value'  => 'Giá trị',
        ],
        'create-success' => 'Webhook đã được tạo thành công',
        'update-success' => 'Webhook đã được cập nhật thành công',
        'delete-success' => 'Webhook đã được xóa thành công',
        'delete-failed'  => 'Xóa Webhook thất bại',
        'validation'     => [
            'unsafe-url' => 'URL trỏ đến địa chỉ riêng tư, loopback hoặc nội bộ và không được phép.',
            'scheme'     => 'URL phải bắt đầu bằng http:// hoặc https://.',
        ],
        'test' => [
            'payload-message'   => 'Yêu cầu kiểm tra webhook Unopim',
            'connection-failed' => 'Không thể truy cập URL. Vui lòng kiểm tra URL.',
            'unreachable'       => 'Không thể truy cập URL (HTTP :code).',
            'reachable'         => 'URL có thể truy cập.',
        ],
        'prune' => [
            'disabled' => 'Việc lưu giữ nhật ký webhook đã bị tắt; không có gì được dọn dẹp.',
            'done'     => 'Đã dọn dẹp :count nhật ký webhook cũ hơn :days ngày.',
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
                        'event'            => 'Sự kiện',
                        'created_at'       => 'Ngày/Giờ',
                        'user'             => 'Người dùng',
                        'status'           => 'Trạng thái',
                        'success'          => 'Thành công',
                        'failed'           => 'Thất bại',
                        'server_error'     => 'Lỗi máy chủ',
                        'timeout_or_error' => 'Hết thời gian/Lỗi',
                        'delete'           => 'Xóa',
                        'view'             => 'Xem',
                    ],
                    'title'          => 'Nhật ký Webhook',
                    'show-title'     => 'Chi tiết nhật ký Webhook',
                    'sent-payload'   => 'Payload đã gửi',
                    'response'       => 'Phản hồi',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Không có payload nào được ghi lại cho nhật ký này.',
                    'load-failed'    => 'Không thể tải chi tiết nhật ký.',
                    'delete-success' => 'Nhật ký Webhook đã được xóa thành công',
                    'delete-failed'  => 'Xóa nhật ký Webhook thất bại một cách bất ngờ',
                    'unauthorized'   => 'Hành động này không được phép',
                ],
            ],
        ],
    ],
];
