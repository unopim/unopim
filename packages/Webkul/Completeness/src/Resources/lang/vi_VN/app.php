<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Mức độ hoàn thiện',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Mức độ hoàn thiện đã được cập nhật thành công',
                    'title'               => 'Mức độ hoàn thiện',
                    'configure'           => 'Cấu hình mức độ hoàn thiện',
                    'channel-required'    => 'Bắt buộc trong kênh',
                    'save-btn'            => 'Lưu',
                    'back-btn'            => 'Quay lại',
                    'mass-update-success' => 'Mức độ hoàn thiện đã được cập nhật thành công',
                    'datagrid'            => [
                        'code'             => 'Mã',
                        'name'             => 'Tên',
                        'channel-required' => 'Bắt buộc trong kênh',
                        'actions'          => [
                            'change-requirement' => 'Thay đổi yêu cầu hoàn thiện',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'Không có',
                    'completeness'                 => 'Hoàn thành',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Mức độ hoàn thiện',
                    'subtitle' => 'Mức độ hoàn thiện trung bình',
                ],
                'required-attributes' => 'thiếu các thuộc tính bắt buộc',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Tính toán mức độ hoàn thiện đã hoàn tất',
        'completeness-calculated'        => 'Mức độ hoàn thiện đã được tính cho :count sản phẩm.',
        'completeness-calculated-family' => 'Mức độ hoàn thiện đã được tính cho :count sản phẩm trong nhóm ":family".',
        'email-subject'                  => 'Tính toán mức độ hoàn thiện đã hoàn tất',
        'email-greeting'                 => 'Xin chào,',
        'email-body'                     => 'Việc tính toán mức độ hoàn thiện đã hoàn tất cho :count sản phẩm.',
        'email-body-family'              => 'Việc tính toán mức độ hoàn thiện đã hoàn tất cho :count sản phẩm trong nhóm thuộc tính ":family".',
        'email-footer'                   => 'Bạn có thể xem chi tiết mức độ hoàn thiện trên bảng điều khiển của mình.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Sản phẩm đã tính toán',
                'suggestion'          => [
                    'low'     => 'Mức độ hoàn thiện thấp, hãy thêm chi tiết để cải thiện.',
                    'medium'  => 'Tiếp tục, hãy tiếp tục bổ sung thông tin.',
                    'high'    => 'Gần hoàn thành, chỉ còn một vài chi tiết.',
                    'perfect' => 'Thông tin sản phẩm đã hoàn toàn đầy đủ.',
                ],
            ],
        ],
    ],
];
