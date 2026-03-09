<?php

return [

    'attribute' => [
        'measurement' => 'Đo lường',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Tạo Nhóm Đo lường',
            'code'     => 'Mã',
            'standard' => 'Mã đơn vị chuẩn',
            'symbol'   => 'Ký hiệu',
            'save'     => 'Lưu',
        ],

        'edit' => [
            'measurement_edit' => 'Chỉnh sửa Nhóm Đo lường',
            'back'             => 'Quay lại',
            'save'             => 'Lưu',
            'general'          => 'Chung',
            'code'             => 'Mã',
            'label'            => 'Nhãn',
            'units'            => 'Đơn vị',
            'create_units'     => 'Tạo Đơn vị',
        ],

        'unit' => [
            'edit_unit'   => 'Chỉnh sửa Đơn vị',
            'create_unit' => 'Tạo Đơn vị',
            'symbol'      => 'Ký hiệu',
            'save'        => 'Lưu',
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
            'updated'      => 'Nhóm đo lường đã được cập nhật thành công.',
            'deleted'      => 'Nhóm đo lường đã được xóa thành công.',
            'mass_deleted' => 'Các nhóm đo lường được chọn đã xóa thành công.',
        ],

        'unit' => [
            'not_found'         => 'Không tìm thấy nhóm đo lường.',
            'already_exists'    => 'Mã đơn vị đã tồn tại.',
            'not_foundd'        => 'Không tìm thấy đơn vị.',
            'deleted'           => 'Đơn vị đã xóa thành công.',
            'no_items_selected' => 'Không có mục nào được chọn.',
            'mass_deleted'      => 'Các đơn vị đo lường được chọn đã xóa thành công.',
        ],
    ],

];
