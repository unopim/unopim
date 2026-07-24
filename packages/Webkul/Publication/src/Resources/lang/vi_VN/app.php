<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Xuất bản',
            'info'     => 'Lớp phân phối công khai cho nội dung đã xuất bản theo từng ngôn ngữ.',
            'settings' => [
                'title'                  => 'Cài đặt xuất bản',
                'enabled'                => 'Đã bật',
                'base-url'               => 'URL cơ sở',
                'cache-ttl'              => 'TTL bộ nhớ đệm (giây)',
                'rate-limit'             => 'Giới hạn tốc độ (yêu cầu/phút)',
                'indexable'              => 'Cho phép công cụ tìm kiếm lập chỉ mục',
                'enabled-hint'           => 'Công tắc chính cho tầng phục vụ công khai. Khi tắt, mọi URL hộ chiếu công khai đều trả về 404 và menu hộ chiếu bị ẩn.',
                'base-url-hint'          => 'Địa chỉ công khai nơi hộ chiếu được phục vụ, dùng để tạo mã QR và liên kết chia sẻ. Để trống để dùng tên miền của chính trang này.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl-hint'         => 'Thời gian một hộ chiếu công khai đã kết xuất được lưu vào bộ nhớ đệm trước khi được dựng lại. Giá trị cao hơn giảm tải; giá trị thấp hơn phản ánh chỉnh sửa nhanh hơn.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit-hint'        => 'Số yêu cầu hộ chiếu công khai tối đa mỗi phút từ một khách truy cập trước khi bị giới hạn.',
                'rate-limit-placeholder' => '60',
                'indexable-hint'         => 'Cho phép công cụ tìm kiếm lập chỉ mục các trang hộ chiếu công khai. Tắt để hộ chiếu vẫn truy cập được qua liên kết nhưng ẩn khỏi kết quả tìm kiếm.',
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
        'product-delete-blocked' => 'Không thể xóa sản phẩm này khi vẫn còn hộ chiếu đã xuất bản. Vui lòng thu hồi trước.',
        'channel-delete-blocked' => 'Không thể xóa kênh này khi vẫn còn hộ chiếu đã xuất bản. Vui lòng thu hồi trước.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Không tìm thấy hộ chiếu.',
            'notice'  => 'Hộ chiếu sản phẩm này không khả dụng. Có thể nó chưa được xuất bản hoặc liên kết không chính xác.',
        ],
        '429' => [
            'heading' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau giây lát.',
            'notice'  => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng đợi một lát và thử lại.',
        ],
        'withdrawn' => [
            'heading' => 'Hộ chiếu này không còn khả dụng.',
            'notice'  => 'Bản ghi này được lưu giữ vì mục đích minh bạch nhưng không còn được duy trì tích cực.',
        ],
    ],
];
