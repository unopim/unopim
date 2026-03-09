<?php

return [

    'attribute' => [
        'measurement' => '측정',
    ],

    'measurement' => [
        'index' => [
            'create'   => '측정 패밀리 생성',
            'code'     => '코드',
            'standard' => '표준 단위 코드',
            'symbol'   => '기호',
            'save'     => '저장',
        ],

        'edit' => [
            'measurement_edit' => '측정 패밀리 편집',
            'back'             => '뒤로',
            'save'             => '저장',
            'general'          => '일반',
            'code'             => '코드',
            'label'            => '라벨',
            'units'            => '단위',
            'create_units'     => '단위 생성',
        ],

        'unit' => [
            'edit_unit'   => '단위 편집',
            'create_unit' => '단위 생성',
            'symbol'      => '기호',
            'save'        => '저장',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => '측정 패밀리',
        'measurement_family'   => '측정 패밀리',
        'measurement_unit'     => '측정 단위',
    ],

    'datagrid' => [
        'labels'        => '라벨',
        'code'          => '코드',
        'standard_unit' => '표준 단위',
        'unit_count'    => '단위 수',
        'is_standard'   => '표준 단위로 표시',
    ],

    'messages' => [
        'family' => [
            'updated'      => '측정 패밀리가 성공적으로 업데이트되었습니다.',
            'deleted'      => '측정 패밀리가 성공적으로 삭제되었습니다.',
            'mass_deleted' => '선택한 측정 패밀리가 성공적으로 삭제되었습니다.',
        ],

        'unit' => [
            'not_found'         => '측정 패밀리를 찾을 수 없습니다.',
            'already_exists'    => '단위 코드는 이미 존재합니다.',
            'not_foundd'        => '단위를 찾을 수 없습니다.',
            'deleted'           => '단위가 성공적으로 삭제되었습니다.',
            'no_items_selected' => '선택된 항목이 없습니다.',
            'mass_deleted'      => '선택한 측정 단위가 성공적으로 삭제되었습니다.',
        ],
    ],

];
