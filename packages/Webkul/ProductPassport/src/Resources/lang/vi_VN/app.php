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
                'title'                              => 'Cài đặt hộ chiếu sản phẩm',
                'enabled'                            => 'Đã bật',
                'auto-publish'                       => 'Tự động xuất bản khi lưu',
                'completeness-threshold'             => 'Ngưỡng hoàn thiện (%)',
                'operator-name'                      => 'Tên nhà điều hành kinh tế',
                'operator-address'                   => 'Địa chỉ nhà điều hành kinh tế',
                'operator-eu-rep'                    => 'Đại diện được ủy quyền tại EU',
                'support-url'                        => 'URL hỗ trợ',
                'enabled-hint'                       => 'Bật tính năng Hộ chiếu Sản phẩm Kỹ thuật số cho danh mục này. Khi tắt, bảng và lưới hộ chiếu bị ẩn.',
                'auto-publish-hint'                  => 'Tự động xuất bản một phiên bản hộ chiếu mỗi khi sản phẩm được lưu và đạt ngưỡng đầy đủ. Để tắt để xuất bản thủ công.',
                'completeness-threshold-hint'        => 'Mức độ đầy đủ tối thiểu của sản phẩm, tính theo phần trăm, cần đạt trước khi có thể xuất bản hộ chiếu cho một ngôn ngữ.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Tên pháp lý của nhà sản xuất hoặc nhà điều hành kinh tế chịu trách nhiệm, hiển thị trên mọi hộ chiếu công khai theo yêu cầu của quy định ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Địa chỉ bưu chính đã đăng ký của nhà điều hành kinh tế, hiển thị trên hộ chiếu công khai để truy xuất nguồn gốc.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Tên và thông tin liên hệ của đại diện được ủy quyền tại EU, bắt buộc khi nhà sản xuất được thành lập bên ngoài EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Trang công khai nơi khách hàng có thể tìm trợ giúp hoặc thông tin bảo hành. Hiển thị dưới dạng liên kết trên mọi hộ chiếu.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Hộ Chiếu Số Sản Phẩm',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Thành phần vật liệu',
        'dpp_substances_of_concern'     => 'Chất cần quan tâm',
        'dpp_recycled_content_pct'      => 'Hàm lượng tái chế (%)',
        'dpp_carbon_footprint'          => 'Dấu chân carbon',
        'dpp_energy_consumption'        => 'Mức tiêu thụ năng lượng',
        'dpp_durability_statement'      => 'Tuyên bố về độ bền',
        'dpp_repairability_score'       => 'Điểm khả năng sửa chữa',
        'dpp_spare_parts_availability'  => 'Tình trạng sẵn có phụ tùng thay thế',
        'dpp_care_instructions'         => 'Hướng dẫn bảo quản',
        'dpp_disassembly_guide'         => 'Hướng dẫn tháo rời',
        'dpp_manufacturer_name'         => 'Tên nhà sản xuất',
        'dpp_manufacturing_site'        => 'Địa điểm sản xuất',
        'dpp_country_of_origin'         => 'Xuất xứ',
        'dpp_supply_chain_notes'        => 'Ghi chú chuỗi cung ứng',
        'dpp_end_of_life_instructions'  => 'Hướng dẫn khi hết vòng đời',
        'dpp_take_back_scheme'          => 'Chương trình thu hồi',
        'dpp_declaration_of_conformity' => 'Tuyên bố hợp quy',
        'dpp_test_reports'              => 'Báo cáo thử nghiệm',
        'dpp_certificates'              => 'Chứng chỉ',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Mã định danh mẫu',
        'dpp_batch_identifier'          => 'Mã định danh lô',
        'dpp_warranty_terms'            => 'Điều khoản bảo hành',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Các thuộc tính Hộ Chiếu Số Sản Phẩm đã được cài đặt thành công.',
        ],
    ],

    'public' => [
        'badge'         => 'Hộ chiếu Sản phẩm Kỹ thuật số EU',
        'search-locale' => 'Ngôn ngữ tìm kiếm',
        'sections'      => [
            'passport' => 'Hộ chiếu Sản phẩm',
        ],
        'title'      => 'Hộ chiếu Sản phẩm Kỹ thuật số',
        'identifier' => [
            'title'        => 'Nhận dạng',
            'gtin'         => 'GTIN',
            'model'        => 'Mẫu',
            'batch'        => 'Lô',
            'not-provided' => 'Chưa cung cấp',
        ],
        'operator' => [
            'title' => 'Nhà điều hành kinh tế',
        ],
        'documents' => [
            'title' => 'Tài liệu',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'Việc xuất bản hộ chiếu hiện đang bị tắt. Các hộ chiếu hiện có được hiển thị bên dưới để quản lý (xem và thu hồi).',
            'title'           => 'Hộ chiếu Sản phẩm Kỹ thuật số',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kênh',
            'status'          => 'Trạng thái',
            'live-locales'    => 'Ngôn ngữ đang hoạt động',
            'last-published'  => 'Xuất bản lần cuối',
            'withdraw'        => 'Thu hồi',
        ],
        'publish-queued' => 'Việc xuất bản hộ chiếu đã được đưa vào hàng đợi.',
        'withdrawn'      => 'Đã thu hồi hộ chiếu thành công.',
        'mass-publish'   => [
            'action' => 'Xuất bản Hộ chiếu Sản phẩm Kỹ thuật số',
            'queued' => 'Đã đưa việc xuất bản hộ chiếu vào hàng đợi cho :count sản phẩm.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Hộ chiếu',
            'view'     => 'Xem',
            'publish'  => 'Xuất bản',
            'withdraw' => 'Thu hồi',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Hộ chiếu',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Đang xuất bản…',
                    'queued'              => 'Đang chờ',
                    'title'               => 'Hộ chiếu Sản phẩm Kỹ thuật số',
                    'publishing-disabled' => 'Việc xuất bản hộ chiếu bị vô hiệu hóa cho kênh này.',
                    'locale'              => 'Ngôn ngữ',
                    'version'             => 'Phiên bản',
                    'published-at'        => 'Ngày xuất bản',
                    'missing-fields'      => 'Trường còn thiếu',
                    'not-published'       => 'Chưa xuất bản',
                    'unscored'            => 'Chưa đánh giá',
                    'publish'             => 'Xuất bản',
                    'republish'           => 'Xuất bản lại',
                    'publish-all'         => 'Xuất bản tất cả ngôn ngữ',
                    'auto-publish-on'     => 'Tự động xuất bản đang bật — hộ chiếu được xuất bản tự động khi sản phẩm được lưu và đạt ngưỡng hoàn thiện. Dùng các nút để xuất bản ngay.',
                    'auto-publish-off'    => 'Xuất bản thủ công — dùng các nút để xuất bản hộ chiếu của sản phẩm này cho từng ngôn ngữ.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => ':attribute phải là một GTIN hợp lệ (8, 12, 13 hoặc 14 chữ số với chữ số kiểm tra chính xác).',
    ],
    'mapping' => [
        'title' => 'Ánh xạ trường hộ chiếu sản phẩm',
        'info' => 'Lấy từng trường hộ chiếu từ một thuộc tính bạn đã duy trì. Để trống ánh xạ một trường để quay lại thuộc tính hộ chiếu chuyên dụng của nó.',
        'menu' => 'Ánh xạ trường',
        'field' => 'Trường hộ chiếu',
        'source' => 'Thuộc tính nguồn',
        'select-source' => 'Sử dụng thuộc tính hộ chiếu',
        'save-btn' => 'Lưu ánh xạ',
        'type-mismatch' => 'Nguồn đã chọn không tương thích với loại trường hộ chiếu này.',
        'saved' => 'Đã lưu ánh xạ trường thành công.',
    ],

];
