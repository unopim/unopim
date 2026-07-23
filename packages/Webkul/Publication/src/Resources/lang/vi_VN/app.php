<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Xuất bản',
            'info'     => 'Lớp phân phối công khai cho nội dung đã xuất bản theo từng ngôn ngữ.',
            'settings' => [
                'title'      => 'Cài đặt xuất bản',
                'enabled'    => 'Đã bật',
                'base-url'   => 'URL cơ sở',
                'cache-ttl'  => 'TTL bộ nhớ đệm (giây)',
                'rate-limit' => 'Giới hạn tốc độ (yêu cầu/phút)',
                'indexable'  => 'Cho phép công cụ tìm kiếm lập chỉ mục',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Bản nháp',
            'published' => 'Đã xuất bản',
            'withdrawn' => 'Đã rút lại',
            'redacted'  => 'Đã ẩn (biên tập)',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Không tìm thấy hộ chiếu.',
        ],
        '429' => [
            'heading' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau giây lát.',
        ],
        'withdrawn' => [
            'heading' => 'Hộ chiếu này không còn khả dụng.',
        ],
    ],
];
