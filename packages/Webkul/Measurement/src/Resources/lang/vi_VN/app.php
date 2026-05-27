<?php

return [

    'attribute' => [
        'measurement' => 'Đo lường',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Tạo Nhóm Đo lường',
            'code'                  => 'Mã',
            'standard'              => 'Mã đơn vị chuẩn',
            'symbol'                => 'Ký hiệu',
            'save'                  => 'Lưu',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Chỉnh sửa Nhóm Đo lường',
            'back'                  => 'Quay lại',
            'save'                  => 'Lưu',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Chung',
            'code'                  => 'Mã',
            'label'                 => 'Nhãn',
            'units'                 => 'Đơn vị',
            'create_units'          => 'Tạo Đơn vị',
        ],

        'unit' => [
            'edit_unit'             => 'Chỉnh sửa Đơn vị',
            'create_unit'           => 'Tạo Đơn vị',
            'symbol'                => 'Ký hiệu',
            'save'                  => 'Lưu',
            'conversion_operation'  => 'Hoạt động chuyển đổi',
            'add_new_operation'     => 'Thêm hoạt động mới',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Nhóm Đo lường',
        'measurement_family'   => 'Nhóm Đo lường',
        'measurement_unit'     => 'Đơn vị Đo lường',
    ],

    'datagrid' => [
        'labels'        => 'Nhãn',
        'code'          => 'Mã',
        'standard_unit' => 'Đơn vị chuẩn',
        'unit_count'    => 'Số lượng đơn vị',
        'is_standard'   => 'Đánh dấu là đơn vị chuẩn',
    ],

    'messages' => [
        'family' => [
            'created'      => 'Nhóm đo lường đã được tạo thành công.',
            'updated'      => 'Nhóm đo lường đã được cập nhật thành công.',
            'deleted'      => 'Nhóm đo lường đã được xóa thành công.',
            'mass_deleted' => 'Các nhóm đo lường được chọn đã xóa thành công.',
        ],

        'unit' => [
            'not_found'         => 'Không tìm thấy nhóm đo lường.',
            'already_exists'    => 'Mã đơn vị đã tồn tại.',
            'units_not_found'        => 'Không tìm thấy đơn vị.',
            'deleted'           => 'Đơn vị đã xóa thành công.',
            'no_items_selected' => 'Không có mục nào được chọn.',
            'mass_deleted'      => 'Các đơn vị đo lường được chọn đã xóa thành công.',
        ],
    ],

];
