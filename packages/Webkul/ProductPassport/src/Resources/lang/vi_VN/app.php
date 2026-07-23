<?php

return [
    'type' => [
        'label' => 'Hộ chiếu Sản phẩm Kỹ thuật số',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Hộ chiếu Sản phẩm',
            'info'     => 'Cài đặt xuất bản hộ chiếu sản phẩm kỹ thuật số.',
            'settings' => [
                'title'                  => 'Cài đặt hộ chiếu sản phẩm',
                'enabled'                => 'Đã bật',
                'auto-publish'           => 'Tự động xuất bản khi lưu',
                'completeness-threshold' => 'Ngưỡng hoàn thiện (%)',
                'operator-name'          => 'Tên nhà điều hành kinh tế',
                'operator-address'       => 'Địa chỉ nhà điều hành kinh tế',
                'operator-eu-rep'        => 'Đại diện được ủy quyền tại EU',
                'support-url'            => 'URL hỗ trợ',
            ],
        ],
    ],
];
