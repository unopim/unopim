<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Các sản phẩm',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL key: \'%s\' đã được tạo cho một mục có SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Giá trị không hợp lệ cho cột gia đình thuộc tính (gia đình thuộc tính không tồn tại?)',
                    'invalid-type'                             => 'Loại sản phẩm không hợp lệ hoặc không được hỗ trợ',
                    'sku-not-found'                            => 'Sản phẩm với SKU đã cho không được tìm thấy',
                    'super-attribute-not-found'                => 'Thuộc tính cấu hình với mã: \'%s\' không được tìm thấy hoặc không thuộc về gia đình thuộc tính: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Thuộc tính có thể cấu hình yêu cầu để tạo mô hình sản phẩm',
                    'configurable-attributes-wrong-type'       => 'Chỉ các loại thuộc tính đã chọn không dựa trên vị trí hoặc kênh mới có thể được chọn làm thuộc tính có thể cấu hình cho sản phẩm cấu hình',
                    'variant-configurable-attribute-not-found' => 'Thuộc tính cấu hình biến thể: :code cần thiết để tạo ra',
                    'not-unique-variant-product'               => 'Sản phẩm với các thuộc tính cấu hình giống nhau đã tồn tại.',
                    'channel-not-exist'                        => 'Kênh này không tồn tại.',
                    'locale-not-in-channel'                    => 'Ngôn ngữ này không được chọn trong kênh.',
                    'locale-not-exist'                         => 'Ngôn ngữ này không tồn tại',
                    'not-unique-value'                         => 'Giá trị :code phải duy nhất.',
                    'incorrect-family-for-variant'             => 'Gia đình phải giống như gia đình chính',
                    'parent-not-exist'                         => 'Cha mẹ không tồn tại.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Các danh mục',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Bạn không thể xóa danh mục gốc có liên quan đến một kênh',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Thuộc tính',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Mã thuộc tính :code đã được sử dụng.',
                    'code_not_found_to_delete'             => 'Không tìm thấy mã thuộc tính để xóa.',
                    'code_is_system_and_cannot_be_deleted' => 'Không thể xóa thuộc tính hệ thống.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Nhóm thuộc tính',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Mã nhóm thuộc tính :code đã được sử dụng.',
                    'code_not_found_to_delete'             => 'Không tìm thấy mã nhóm thuộc tính để xóa.',
                    'code_is_system_and_cannot_be_deleted' => 'Không thể xóa nhóm thuộc tính hệ thống.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Họ thuộc tính',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Mã họ thuộc tính :code đã được sử dụng.',
                    'code_not_found_to_delete' => 'Không tìm thấy mã họ thuộc tính để xóa.',
                    'invalid-attribute-group'  => 'Nhóm thuộc tính ":code" không tồn tại.',
                    'invalid-attribute'        => 'Thuộc tính ":code" không tồn tại.',
                    'invalid-channel'          => 'Kênh ":code" không tồn tại.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Tùy chọn thuộc tính',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Mã tùy chọn thuộc tính :code đã được sử dụng.',
                    'code_not_found_to_delete' => 'Không tìm thấy mã tùy chọn thuộc tính để xóa.',
                    'locale-not-exist'         => 'Vị trí ":code" không tồn tại.',
                    'invalid-attribute'        => 'Thuộc tính ":code" không tồn tại.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Các sản phẩm',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL key: \'%s\' đã được tạo cho một mục có SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Giá trị không hợp lệ cho cột gia đình thuộc tính (gia đình thuộc tính không tồn tại?)',
                    'invalid-type'              => 'Loại sản phẩm không hợp lệ hoặc không được hỗ trợ',
                    'sku-not-found'             => 'Sản phẩm với SKU đã cho không được tìm thấy',
                    'super-attribute-not-found' => 'Thuộc tính cấu hình với mã: \'%s\' không được tìm thấy hoặc không thuộc về gia đình thuộc tính: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Các danh mục',
        ],
        'attributes' => [
            'title' => 'Thuộc tính',
        ],
        'attribute-groups' => [
            'title' => 'Nhóm thuộc tính',
        ],
        'attribute-families' => [
            'title' => 'Họ thuộc tính',
        ],
        'attribute-options' => [
            'title' => 'Tùy chọn thuộc tính',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Các cột với số "%s" có các tiêu đề trống.',
            'column-name-invalid'  => 'Tên cột không hợp lệ: "%s".',
            'column-not-found'     => 'Các cột yêu cầu không được tìm thấy: %s.',
            'column-numbers'       => 'Số cột không khớp với số hàng trong tiêu đề.',
            'invalid-attribute'    => 'Tiêu đề chứa thuộc tính không hợp lệ: "%s".',
            'system'               => 'Lỗi hệ thống không mong muốn xảy ra.',
            'wrong-quotes'         => 'Dùng dấu ngoặc kép cong thay cho dấu ngoặc kép thẳng.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Bắt đầu công việc',
        'completed' => 'Công việc đã hoàn thành',
    ],
];
