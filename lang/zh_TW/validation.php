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
    'accepted'        => ':attribute 必須被接受。',
    'active_url'      => ':attribute 不是有效的 URL。',
    'after'           => ':attribute 必須是 :date 之後的日期。',
    'after_or_equal'  => ':attribute 必須是 :date 之後或等於 :date 的日期。',
    'alpha'           => ':attribute 只能包含字母。',
    'alpha_dash'      => ':attribute 只能包含字母、數字、破折號和底線。',
    'alpha_num'       => ':attribute 只能包含字母和數字。',
    'array'           => ':attribute 必須是陣列。',
    'before'          => ':attribute 必須是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必須是 :date 之前或等於 :date 的日期。',

    'between' => [
        'numeric' => ':attribute 必須介於 :min 和 :max 之間。',
        'file'    => ':attribute 必須介於 :min 和 :max KB 之間。',
        'string'  => ':attribute 必須介於 :min 和 :max 字符之間。',
        'array'   => ':attribute 必須有 :min 到 :max 項目。',
    ],

    'boolean'        => ':attribute 必須是 true 或 false。',
    'confirmed'      => ':attribute 確認不相符。',
    'date'           => ':attribute 不是有效的日期。',
    'date_format'    => ':attribute 不符合格式 :format。',
    'different'      => ':attribute 和 :other 必須不同。',
    'digits'         => ':attribute 必須是 :digits 位數字。',
    'digits_between' => ':attribute 必須是 :min 到 :max 位數字。',
    'dimensions'     => ':attribute 的圖片尺寸無效。',
    'distinct'       => ':attribute 有重複的值。',
    'email'          => ':attribute 必須是有效的電子郵件地址。',
    'exists'         => '選擇的 :attribute 無效。',
    'file'           => ':attribute 必須是檔案。',
    'filled'         => ':attribute 必須有值。',

    'gt' => [
        'numeric' => ':attribute 必須大於 :value。',
        'file'    => ':attribute 必須大於 :value KB。',
        'string'  => ':attribute 必須長於 :value 字符。',
        'array'   => ':attribute 必須有超過 :value 項目。',
    ],

    'gte' => [
        'numeric' => ':attribute 必須大於或等於 :value。',
        'file'    => ':attribute 必須大於或等於 :value KB。',
        'string'  => ':attribute 必須長於或等於 :value 字符。',
        'array'   => ':attribute 必須有至少 :value 項目。',
    ],

    'image'    => ':attribute 必須是圖片。',
    'in'       => '選擇的 :attribute 無效。',
    'in_array' => ':attribute 不存在於 :other 中。',
    'integer'  => ':attribute 必須是整數。',
    'ip'       => ':attribute 必須是有效的 IP 地址。',
    'ipv4'     => ':attribute 必須是有效的 IPv4 地址。',
    'ipv6'     => ':attribute 必須是有效的 IPv6 地址。',
    'json'     => ':attribute 必須是有效的 JSON 字符串。',

    'lt' => [
        'numeric' => ':attribute 必須小於 :value。',
        'file'    => ':attribute 必須小於 :value KB。',
        'string'  => ':attribute 必須短於 :value 字符。',
        'array'   => ':attribute 必須少於 :value 項目。',
    ],

    'lte' => [
        'numeric' => ':attribute 必須小於或等於 :value。',
        'file'    => ':attribute 必須小於或等於 :value KB。',
        'string'  => ':attribute 必須短於或等於 :value 字符。',
        'array'   => ':attribute 不能有超過 :value 項目。',
    ],

    'max' => [
        'numeric' => ':attribute 不能大於 :max。',
        'file'    => ':attribute 不能大於 :max KB。',
        'string'  => ':attribute 不能長於 :max 字符。',
        'array'   => ':attribute 不能有超過 :max 項目。',
    ],

    'mimes'     => ':attribute 必須是以下類型的檔案: :values。',
    'mimetypes' => ':attribute 必須是以下類型的檔案: :values。',
    
    'min' => [
        'numeric' => ':attribute 必須大於或等於 :min。',
        'file'    => ':attribute 必須大於或等於 :min KB。',
        'string'  => ':attribute 必須大於或等於 :min 字符。',
        'array'   => ':attribute 必須有至少 :min 項目。',
    ],

    'not_in'               => '選擇的 :attribute 無效。',
    'not_regex'            => ':attribute 格式無效。',
    'numeric'              => ':attribute 必須是數字。',
    'present'              => ':attribute 欄位必須存在。',
    'regex'                => ':attribute 格式無效。',
    'required'             => ':attribute 欄位是必填的。',
    'required_if'          => ':attribute 欄位在 :other 是 :value 時為必填。',
    'required_unless'      => ':attribute 欄位在 :other 不是 :values 時為必填。',
    'required_with'        => ':attribute 欄位在 :values 有時為必填。',
    'required_with_all'    => ':attribute 欄位在 :values 都有時為必填。',
    'required_without'     => ':attribute 欄位在 :values 沒有時為必填。',
    'required_without_all' => ':attribute 欄位在 :values 都沒有時為必填。',
    'same'                 => ':attribute 和 :other 必須相同。',

    'size' => [
        'numeric' => ':attribute 必須是 :size。',
        'file'    => ':attribute 必須是 :size KB。',
        'string'  => ':attribute 必須是 :size 字符。',
        'array'   => ':attribute 必須有 :size 項目。',
    ],

    'string'   => ':attribute 必須是字符串。',
    'timezone' => ':attribute 必須是有效的時區。',
    'unique'   => ':attribute 已經存在。',
    'uploaded' => ':attribute 上傳失敗。',
    'url'      => ':attribute 格式無效。',

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
            'rule-name' => '自定義訊息',
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
