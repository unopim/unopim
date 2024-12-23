<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'accepted'        => ':attribute 는(은) 동의해야 합니다.',
    'active_url'      => ':attribute 는(은) 유효한 URL 주소가 아닙니다.',
    'after'           => ':attribute 는(은) :date 이후의 날짜여야 합니다.',
    'after_or_equal'  => ':attribute 는(은) :date와 같은 날짜이거나 이후의 날짜여야 합니다.',
    'alpha'           => ':attribute 는(은) 문자만 포함할 수 있습니다.',
    'alpha_dash'      => ':attribute 는(은) 문자, 숫자, 대시 및 밑줄만 포함할 수 있습니다.',
    'alpha_num'       => ':attribute 는(은) 문자와 숫자만 포함할 수 있습니다.',
    'array'           => ':attribute 는(은) 배열이어야 합니다.',
    'before'          => ':attribute 는(은) :date 이전의 날짜여야 합니다.',
    'before_or_equal' => ':attribute 는(은) :date와 같은 날짜이거나 이전의 날짜여야 합니다.',

    'between' => [
        'numeric' => ':attribute 는(은) :min 과 :max 사이여야 합니다.',
        'file'    => ':attribute 는(은) :min 과 :max 킬로바이트 사이여야 합니다.',
        'string'  => ':attribute 는(은) :min 과 :max 문자 사이여야 합니다.',
        'array'   => ':attribute 는(은) :min 과 :max 개의 항목을 포함해야 합니다.',
    ],

    'boolean'        => ':attribute 필드는 true 또는 false 여야 합니다.',
    'confirmed'      => ':attribute 확인이 일치하지 않습니다.',
    'date'           => ':attribute 는(은) 유효한 날짜가 아닙니다.',
    'date_format'    => ':attribute 는(은) :format 형식과 일치하지 않습니다.',
    'different'      => ':attribute 와(과) :other는(은) 달라야 합니다.',
    'digits'         => ':attribute 는(은) :digits 자릿수여야 합니다.',
    'digits_between' => ':attribute 는(은) :min 과 :max 자릿수 사이여야 합니다.',
    'dimensions'     => ':attribute 의 이미지 크기가 잘못되었습니다.',
    'distinct'       => ':attribute 필드에 중복된 값이 있습니다.',
    'email'          => ':attribute 는(은) 유효한 이메일 주소여야 합니다.',
    'exists'         => '선택된 :attribute 는(은) 잘못되었습니다.',
    'exists-value'   => ':input 는 존재하지 않습니다.',
    'file'           => ':attribute 는(은) 파일이어야 합니다.',
    'filled'         => ':attribute 필드는 값을 가져야 합니다.',

    'gt' => [
        'numeric' => ':attribute 는(은) :value 보다 커야 합니다.',
        'file'    => ':attribute 는(은) :value 킬로바이트보다 커야 합니다.',
        'string'  => ':attribute 는(은) :value 문자보다 길어야 합니다.',
        'array'   => ':attribute 는(은) :value 개 이상의 항목을 포함해야 합니다.',
    ],

    'gte' => [
        'numeric' => ':attribute 는(은) :value 보다 크거나 같아야 합니다.',
        'file'    => ':attribute 는(은) :value 킬로바이트보다 크거나 같아야 합니다.',
        'string'  => ':attribute 는(은) :value 문자보다 길거나 같아야 합니다.',
        'array'   => ':attribute 는(은) :value 개 이상의 항목을 포함해야 합니다.',
    ],

    'image'    => ':attribute 는(은) 이미지여야 합니다.',
    'in'       => '선택된 :attribute 는(은) 잘못되었습니다.',
    'in_array' => ':attribute 필드는 :other 에 존재해야 합니다.',
    'integer'  => ':attribute 는(은) 정수여야 합니다.',
    'ip'       => ':attribute 는(은) 유효한 IP 주소여야 합니다.',
    'ipv4'     => ':attribute 는(은) 유효한 IPv4 주소여야 합니다.',
    'ipv6'     => ':attribute 는(은) 유효한 IPv6 주소여야 합니다.',
    'json'     => ':attribute 는(은) 유효한 JSON 문자열이어야 합니다.',

    'lt' => [
        'numeric' => ':attribute 는(은) :value 보다 작아야 합니다.',
        'file'    => ':attribute 는(은) :value 킬로바이트보다 작아야 합니다.',
        'string'  => ':attribute 는(은) :value 문자보다 짧아야 합니다.',
        'array'   => ':attribute 는(은) :value 개 이하의 항목을 포함해야 합니다.',
    ],

    'lte' => [
        'numeric' => ':attribute 는(은) :value 보다 작거나 같아야 합니다.',
        'file'    => ':attribute 는(은) :value 킬로바이트보다 작거나 같아야 합니다.',
        'string'  => ':attribute 는(은) :value 문자보다 짧거나 같아야 합니다.',
        'array'   => ':attribute 는(은) :value 개 이하의 항목을 포함해야 합니다.',
    ],

    'max' => [
        'numeric' => ':attribute 는(은) :max 보다 클 수 없습니다.',
        'file'    => ':attribute 는(은) :max 킬로바이트를 초과할 수 없습니다.',
        'string'  => ':attribute 는(은) :max 자를 초과할 수 없습니다.',
        'array'   => ':attribute 는(은) :max 개를 초과할 수 없습니다.',
    ],

    'mimes'     => ':attribute 는(은) :values 유형의 파일이어야 합니다.',
    'mimetypes' => ':attribute 는(은) :values 유형의 파일이어야 합니다.',
    
    'min' => [
        'numeric' => ':attribute 는(은) 최소 :min 이어야 합니다.',
        'file'    => ':attribute 는(은) 최소 :min 킬로바이트여야 합니다.',
        'string'  => ':attribute 는(은) 최소 :min 문자여야 합니다.',
        'array'   => ':attribute 는(은) 최소 :min 개의 항목을 포함해야 합니다.',
    ],

    'not_in'               => '선택된 :attribute 는(은) 잘못되었습니다.',
    'not_regex'            => '형식이 잘못된 :attribute 입니다.',
    'numeric'              => ':attribute 는(은) 숫자여야 합니다.',
    'present'              => ':attribute 필드는 반드시 존재해야 합니다.',
    'regex'                => '형식이 잘못된 :attribute 입니다.',
    'required'             => ':attribute 필드는 필수입니다.',
    'required_if'          => ':attribute 필드는 :other 가 :value 일 때 필수입니다.',
    'required_unless'      => ':attribute 필드는 :other 가 :values 가 아닌 경우 필수입니다.',
    'required_with'        => ':attribute 필드는 :values 가 있을 때 필수입니다.',
    'required_with_all'    => ':attribute 필드는 :values 가 있을 때 필수입니다.',
    'required_without'     => ':attribute 필드는 :values 가 없을 때 필수입니다.',
    'required_without_all' => ':attribute 필드는 :values 가 모두 없을 때 필수입니다.',
    'same'                 => ':attribute 와(과) :other 는(은) 동일해야 합니다.',

    'size' => [
        'numeric' => ':attribute 는(은) :size 여야 합니다.',
        'file'    => ':attribute 는(은) :size 킬로바이트여야 합니다.',
        'string'  => ':attribute 는(은) :size 문자여야 합니다.',
        'array'   => ':attribute 는(은) :size 개의 항목을 포함해야 합니다.',
    ],

    'string'   => ':attribute 는(은) 문자열이어야 합니다.',
    'timezone' => ':attribute 는(은) 유효한 시간대여야 합니다.',
    'unique'   => ':attribute 는(은) 이미 존재합니다.',
    'uploaded' => ':attribute 업로드에 실패했습니다.',
    'url'      => ':attribute 형식이 잘못되었습니다.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => '맞춤 메시지',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [],
];
