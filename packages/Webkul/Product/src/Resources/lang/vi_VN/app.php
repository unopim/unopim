<?php

return [
    'acl' => [
        'variant-structures' => 'Cấu trúc biến thể',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'sao-chep-:value',
        'copy-of'                       => 'Sao chép :value',
        'variant-already-exist-message' => 'Biến thể có cùng tùy chọn thuộc tính đã tồn tại.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Sản phẩm loại :type không thể sao chép',
    ],

    'type'     => [
        'configurable'  => 'Cấu hình',
        'simple'        => 'Đơn giản',
        'variant-group' => 'Nhóm biến thể',
        'variant_group' => 'Nhóm biến thể',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Phát hiện tham chiếu vòng trong mẫu: :path.',
            'currency-not-allowed'    => 'Chỉ thuộc tính giá mới chấp nhận tiền tệ, nhưng ":attribute" không phải là thuộc tính giá.',
            'currency-not-enabled'    => 'Tiền tệ ":currency" được dùng trong mẫu chưa được bật cho kênh nào.',
            'currency-not-in-channel' => 'Tiền tệ ":currency" chưa được bật cho kênh ":channel".',
            'filter-failed'           => 'Không thể áp dụng bộ lọc ":filter" cho giá trị của ":attribute".',
            'filter-not-supported'    => 'Không thể áp dụng bộ lọc ":filter" cho thuộc tính ":attribute".',
            'format-date-position'    => 'Bộ lọc format_date phải là bộ lọc đầu tiên trong ":expression".',
            'inside-html-tag'         => 'Không thể đặt biểu thức mẫu bên trong thẻ hoặc thuộc tính HTML như liên kết và hình ảnh.',
            'invalid-filter-argument' => 'Bộ lọc ":filter" trong ":expression" bị thiếu đối số hoặc có đối số không hợp lệ.',
            'invalid-reference'       => 'Mẫu được lưu trong ":attribute" không hợp lệ và không thể hiển thị.',
            'invalid-syntax'          => 'Biểu thức mẫu ":expression" không hợp lệ. Hãy dùng mã thuộc tính, theo sau là các bộ lọc tùy chọn, ví dụ: name | upper.',
            'max-depth'               => 'Tham chiếu mẫu được lồng sâu hơn :max cấp tại ":attribute".',
            'missing-value'           => 'Thuộc tính ":attribute" không có giá trị cho kênh và ngôn ngữ này.',
            'not-in-family'           => 'Thuộc tính ":attribute" được dùng trong mẫu không thuộc họ sản phẩm.',
            'output-too-large'        => 'Mẫu đã hiển thị lớn hơn :max byte và đã bị cắt bớt.',
            'self-reference'          => 'Mẫu không thể tham chiếu đến chính thuộc tính ":attribute" của nó.',
            'single-brace'            => 'Tìm thấy ":text" trong dấu ngoặc nhọn đơn. Biểu thức mẫu cần dấu ngoặc nhọn kép.',
            'source-too-long'         => 'Một mẫu chỉ có thể dài tối đa :max byte.',
            'too-complex'             => 'Mẫu quá phức tạp để kiểm tra. Hãy rút gọn hoặc đơn giản hóa mẫu.',
            'too-many-expressions'    => 'Một mẫu chỉ có thể chứa tối đa :max biểu thức.',
            'too-many-filters'        => 'Biểu thức mẫu ":expression" chỉ có thể dùng tối đa :max bộ lọc.',
            'unclosed-expression'     => 'Một biểu thức mẫu đã được mở nhưng chưa được đóng.',
            'unknown-attribute'       => 'Thuộc tính ":attribute" được dùng trong mẫu không tồn tại.',
            'unknown-currency'        => 'Tiền tệ ":currency" được dùng trong mẫu không tồn tại.',
            'unknown-filter'          => 'Bộ lọc ":filter" không được hỗ trợ. Các bộ lọc khả dụng: :filters.',
            'unsupported-attribute'   => 'Không thể dùng thuộc tính ":attribute" trong mẫu vì loại của nó không được hỗ trợ.',
        ],
    ],
];
