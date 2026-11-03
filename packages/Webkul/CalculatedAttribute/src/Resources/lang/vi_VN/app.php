<?php

return [

    'attribute' => [
        'calculated' => 'Được tính',
    ],

    'formula' => [
        'title'            => 'Công thức',
        'label'            => 'Công thức',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Giá trị được suy ra từ công thức này mỗi khi sản phẩm được hiển thị, xuất ra hoặc trả về bởi API. Giá trị không bao giờ được lưu và không thể nhập vào.',
        'reference-hint'   => 'Tham chiếu các thuộc tính khác bằng mã của chúng.',
        'available'        => 'Các hàm khả dụng',
        'examples'         => 'Ví dụ',
        'example-concat'   => 'Nối hai thuộc tính, bỏ qua những thuộc tính rỗng',
        'example-category' => 'Đường dẫn nhãn danh mục, nối bằng #',
        'example-fallback' => 'Dùng thuộc tính thứ hai khi thuộc tính đầu rỗng',
        'read-only'        => 'Được tính',
        'read-only-hint'   => 'Giá trị này được tính từ các thuộc tính khác và không thể chỉnh sửa.',

        'variables' => [
            'title'      => 'Các biến khả dụng',
            'sku'        => 'mã SKU của sản phẩm',
            'type'       => 'loại sản phẩm',
            'status'     => 'trạng thái sản phẩm',
            'family'     => 'mã của họ thuộc tính',
            'parent'     => 'mã SKU của sản phẩm cha, đối với biến thể',
            'categories' => 'mã các danh mục đã gán',
            'channel'    => 'kênh đang được hiển thị',
            'locale'     => 'ngôn ngữ đang được hiển thị',
        ],

        'errors' => [
            'syntax'              => 'Không thể phân tích công thức: :detail',
            'unknown-variable'    => 'Thuộc tính hoặc biến ":name" không xác định trong công thức.',
            'disallowed-function' => 'Hàm ":name" không được phép trong công thức.',
            'disallowed-operator' => 'Toán tử ":name" không được phép trong công thức.',
            'reserved-variable'   => 'Tên ":name" đã được dành riêng và không thể dùng trong công thức.',
            'too-long'            => 'Công thức không được dài quá :max ký tự.',
            'empty'               => 'Thuộc tính được tính cần có một công thức.',
            'circular'            => 'Công thức tạo ra phụ thuộc vòng: :cycle',
            'referenced-by'       => 'Không thể xóa thuộc tính này vì nó được dùng trong công thức của: :attributes',
        ],
    ],

];
