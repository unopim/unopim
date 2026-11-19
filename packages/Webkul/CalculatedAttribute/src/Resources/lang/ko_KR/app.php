<?php

return [

    'attribute' => [
        'calculated' => '계산됨',
    ],

    'formula' => [
        'title'            => '수식',
        'label'            => '수식',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => '값은 제품이 표시되거나 내보내지거나 API로 반환될 때마다 이 수식에서 도출됩니다. 값은 저장되지 않으며 가져올 수 없습니다.',
        'reference-hint'   => '다른 속성은 코드로 참조합니다.',
        'available'        => '사용 가능한 함수',
        'examples'         => '예시',
        'example-concat'   => '두 속성을 연결하고 비어 있는 값은 건너뜁니다',
        'example-category' => '카테고리 레이블 경로를 #으로 연결합니다',
        'example-fallback' => '첫 번째 속성이 비어 있으면 두 번째 속성을 사용합니다',
        'read-only'        => '계산됨',
        'read-only-hint'   => '이 값은 다른 속성에서 계산되며 편집할 수 없습니다.',

        'variables' => [
            'title'      => '사용 가능한 변수',
            'sku'        => '제품 SKU',
            'type'       => '제품 유형',
            'status'     => '제품 상태',
            'family'     => '속성 패밀리 코드',
            'parent'     => '변형의 경우 상위 제품의 SKU',
            'categories' => '할당된 카테고리 코드',
            'channel'    => '렌더링 중인 채널',
            'locale'     => '렌더링 중인 언어',
        ],

        'errors' => [
            'syntax'              => '수식을 해석할 수 없습니다: :detail',
            'unknown-variable'    => '수식에 알 수 없는 속성 또는 변수 ":name"이(가) 있습니다.',
            'disallowed-function' => '함수 ":name"은(는) 수식에서 사용할 수 없습니다.',
            'disallowed-operator' => '연산자 ":name"은(는) 수식에서 사용할 수 없습니다.',
            'reserved-variable'   => '이름 ":name"은(는) 예약되어 있어 수식에서 사용할 수 없습니다.',
            'too-long'            => '수식은 :max자를 넘을 수 없습니다.',
            'empty'               => '계산 속성에는 수식이 필요합니다.',
            'circular'            => '수식이 순환 종속성을 만듭니다: :cycle',
            'referenced-by'       => '이 속성은 다음 속성의 수식에서 사용되므로 삭제할 수 없습니다: :attributes',
        ],
    ],

];
