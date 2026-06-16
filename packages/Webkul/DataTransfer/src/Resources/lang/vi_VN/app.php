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
        'channels' => [
            'title'      => 'Kênh',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Không tìm thấy kênh với mã :code để xóa.',
                    'locale-not-found'         => 'Một hoặc nhiều ngôn ngữ không tồn tại.',
                    'root-category-not-found'  => 'Danh mục gốc không tồn tại.',
                    'currency-not-found'       => 'Một hoặc nhiều loại tiền tệ không tồn tại.',
                    'invalid-locale'           => 'Ngôn ngữ không tồn tại.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'fields' => [
            'file-format'         => 'Định dạng tệp',
            'with-media'          => 'Kèm phương tiện',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Trạng thái',
            'enable'         => 'Bật',
            'all'            => 'Tất cả',
        ],
        'products' => [
            'title'              => 'Các sản phẩm',
            'invalid-locales'    => 'Không phải tất cả ngôn ngữ đã chọn đều khả dụng cho các kênh đã chọn.',
            'invalid-currencies' => 'Không phải tất cả tiền tệ đã chọn đều khả dụng cho các kênh đã chọn.',
            'filters'            => [
                'channels'             => 'Kênh',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Tiền tệ',
                'currencies-info'      => 'Thuộc tính giá được xuất theo từng loại tiền tệ đã chọn. Để trống để xuất tất cả tiền tệ của kênh.',
                'locales'              => 'Ngôn ngữ',
                'locales-info'         => 'Thuộc tính có thể bản địa hóa được xuất một lần cho mỗi ngôn ngữ đã chọn. Để trống để xuất tất cả ngôn ngữ của kênh.',
                'attributes'           => 'Thuộc tính',
                'attributes-info'      => 'Chỉ các thuộc tính đã chọn được xuất. Để trống để xuất tất cả thuộc tính trong nhóm.',
                'attribute-families'   => 'Nhóm thuộc tính',
                'categories'           => 'Danh mục',
                'completeness'         => 'Mức độ hoàn chỉnh',
                'completeness-options' => [
                    'none'         => 'Không có điều kiện về mức độ hoàn chỉnh',
                    'at-least-one' => 'Hoàn chỉnh trong ít nhất một ngôn ngữ đã chọn',
                    'all'          => 'Hoàn chỉnh trong tất cả ngôn ngữ đã chọn',
                ],
                'time-condition' => 'Điều kiện thời gian',
                'time-options'   => [
                    'none'              => 'Không có điều kiện ngày',
                    'last-n-days'       => 'Sản phẩm được cập nhật trong N ngày qua',
                    'between-dates'     => 'Sản phẩm được cập nhật giữa hai ngày',
                    'since-last-export' => 'Sản phẩm được cập nhật kể từ lần xuất gần nhất',
                ],
                'time-value'     => 'Số ngày',
                'time-date'      => 'Ngày bắt đầu',
                'time-date-end'  => 'Ngày kết thúc',
                'status'         => 'Trạng thái',
                'status-options' => [
                    'enable'  => 'Bật',
                    'disable' => 'Tắt',
                    'all'     => 'Tất cả',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Định danh',
                'identifiers-info' => 'Dán mỗi dòng một SKU / mã định danh để chỉ xuất những sản phẩm đó. Để trống để xuất tất cả sản phẩm.',
            ],
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
        'channels' => [
            'title' => 'Kênh',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Trạng thái',
                'active' => 'Active',
                'all'    => 'Tất cả',
            ],
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
            'file-empty'           => 'Tệp trống hoặc không chứa dòng tiêu đề. Vui lòng tải lên tệp hợp lệ có dữ liệu.',
        ],
    ],
    'job' => [
        'started'   => 'Bắt đầu công việc',
        'completed' => 'Công việc đã hoàn thành',
    ],
];
