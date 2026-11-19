<?php

return [
    'acl' => [
        'variant-structures' => '변형 구조',
    ],

    'datagrid' => [
        'copy-of-slug'                  => '복사본-:value',
        'copy-of'                       => ':value의 복사본',
        'variant-already-exist-message' => '같은 속성 옵션을 가진 변형이 이미 존재합니다.',
    ],

    'response' => [
        'product-can-not-be-copied' => ':type 유형의 제품은 복사할 수 없습니다.',
    ],

    'type'     => [
        'configurable'  => '설정 가능한',
        'simple'        => '단순한',
        'variant-group' => '변형 그룹',
        'variant_group' => '변형 그룹',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => '순환 템플릿 참조가 감지되었습니다: :path',
            'currency-not-allowed'    => '가격 속성만 통화를 허용하지만 ":attribute"은(는) 가격 속성이 아닙니다.',
            'currency-not-enabled'    => '템플릿에 사용된 통화 ":currency"은(는) 어떤 채널에서도 활성화되어 있지 않습니다.',
            'currency-not-in-channel' => '통화 ":currency"은(는) 채널 ":channel"에 대해 활성화되어 있지 않습니다.',
            'filter-failed'           => '필터 ":filter"을(를) ":attribute"의 값에 적용할 수 없습니다.',
            'filter-not-supported'    => '필터 ":filter"은(는) 속성 ":attribute"에 적용할 수 없습니다.',
            'format-date-position'    => 'format_date 필터는 ":expression"의 첫 번째 필터여야 합니다.',
            'inside-html-tag'         => '템플릿 표현식은 링크나 이미지와 같은 HTML 태그 또는 속성 안에 배치할 수 없습니다.',
            'invalid-filter-argument' => '":expression"의 필터 ":filter"에 인수가 없거나 잘못되었습니다.',
            'invalid-reference'       => '":attribute"에 저장된 템플릿이 유효하지 않아 렌더링할 수 없습니다.',
            'invalid-syntax'          => '템플릿 표현식 ":expression"이(가) 유효하지 않습니다. 속성 코드 뒤에 선택적 필터를 사용하세요. 예: name | upper',
            'max-depth'               => '템플릿 참조가 ":attribute"에서 :max 단계보다 깊게 중첩되어 있습니다.',
            'missing-value'           => '속성 ":attribute"에 이 채널 및 로케일에 대한 값이 없습니다.',
            'not-in-family'           => '템플릿에 사용된 속성 ":attribute"이(가) 제품 패밀리에 속하지 않습니다.',
            'output-too-large'        => '렌더링된 템플릿이 :max 바이트를 초과하여 잘렸습니다.',
            'self-reference'          => '템플릿은 자신의 속성 ":attribute"을(를) 참조할 수 없습니다.',
            'single-brace'            => '단일 중괄호로 묶인 ":text"이(가) 발견되었습니다. 템플릿 표현식에는 이중 중괄호가 필요합니다.',
            'source-too-long'         => '템플릿은 최대 :max 바이트까지 가능합니다.',
            'too-complex'             => '템플릿이 너무 복잡하여 확인할 수 없습니다. 줄이거나 단순화하세요.',
            'too-many-expressions'    => '템플릿에는 최대 :max 개의 표현식만 포함할 수 있습니다.',
            'too-many-filters'        => '템플릿 표현식 ":expression"은(는) 최대 :max 개의 필터를 사용할 수 있습니다.',
            'unclosed-expression'     => '템플릿 표현식이 열렸지만 닫히지 않았습니다.',
            'unknown-attribute'       => '템플릿에 사용된 속성 ":attribute"이(가) 존재하지 않습니다.',
            'unknown-currency'        => '템플릿에 사용된 통화 ":currency"이(가) 존재하지 않습니다.',
            'unknown-filter'          => '필터 ":filter"은(는) 지원되지 않습니다. 사용 가능한 필터: :filters',
            'unsupported-attribute'   => '속성 ":attribute"은(는) 유형이 지원되지 않으므로 템플릿에서 사용할 수 없습니다.',
        ],
    ],
];
