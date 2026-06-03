<?php

return [
    'warning' => [
        'title'           => 'Đã phát hiện APP_URL không khớp',
        'dismiss'         => 'Bỏ qua',
        'lede-before'     => 'Tài nguyên giao diện (CSS, JS) của bạn được gắn với giá trị đã cấu hình',
        'lede-after'      => 'Hãy cập nhật cho khớp với máy chủ bạn đang dùng, nếu không các kiểu và tập lệnh sẽ không tải được.',
        'configured-env'  => 'Đã cấu hình (.env)',
        'mismatch-tag'    => 'KHÔNG KHỚP',
        'actual-browser'  => 'Thực tế (trình duyệt)',
        'in-use-tag'      => 'ĐANG DÙNG',
        'toggle-step'     => 'Bật/tắt bước :number',
        'step-1-title'    => 'Cập nhật APP_URL trong tệp .env của bạn',
        'step-1-hint'     => 'Mở tệp .env của dự án và thay thế dòng APP_URL.',
        'step-2-title'    => 'Xóa bộ nhớ đệm của ứng dụng',
        'step-2-hint'     => 'Chạy lệnh này trong terminal từ thư mục gốc của dự án.',
        'copy'            => 'Sao chép',
        'copied'          => 'Đã sao chép',
        'note-bold'       => 'Sau đó tải lại trang một cách triệt để',
        'note-rest'       => 'để trình duyệt nạp lại các tài nguyên đã cập nhật.',
        'progress'        => 'Hoàn thành :done trong :total bước',
        'all-done'        => 'Hoàn tất',
        'powered-by'      => 'Được cung cấp bởi',
        'open-source-by'  => 'Một dự án mã nguồn mở bởi',
        'copied-toast'    => 'Đã sao chép vào bộ nhớ tạm',
        'still-mismatch'  => 'APP_URL vẫn không khớp. Hãy cập nhật .env và chạy "php artisan optimize:clear".',
        'verify-failed'   => 'Không thể xác minh APP_URL. Vui lòng tải lại trang.',
        'logged-out'      => 'Đã đăng xuất: APP_URL không khớp với máy chủ hiện tại. Hãy cập nhật APP_URL trong .env và chạy "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Đã phát hiện APP_URL không khớp',
        'hint'     => 'Cập nhật APP_URL trong .env thành URL của yêu cầu, sau đó chạy: php artisan optimize:clear',
    ],
];
