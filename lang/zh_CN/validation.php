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
    'accepted'        => ':attribute 必须被接受。',
    'active_url'      => ':attribute 不是有效的 URL。',
    'after'           => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal'  => ':attribute 必须是 :date 之后或等于 :date 的日期。',
    'alpha'           => ':attribute 只能包含字母。',
    'alpha_dash'      => ':attribute 只能包含字母、数字、破折号和下划线。',
    'alpha_num'       => ':attribute 只能包含字母和数字。',
    'array'           => ':attribute 必须是一个数组。',
    'before'          => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 日期 之前或等于 :date 的日期。',

    'between' => [
        'numeric' => ':attribute 必须介于 :min 和 :max 之间。',
        'file'    => ':attribute 必须介于 :min 和 :max 千字节之间。',
        'string'  => ':attribute 必须介于 :min 和 :max 字符之间。',
        'array'   => ':attribute 必须包含 :min 和 :max 项。',
    ],

    'boolean'        => ':attribute 字段必须为真或假。',
    'confirmed'      => ':attribute 确认不匹配。',
    'date'           => ':attribute 不是有效日期。',
    'date_format'    => ':attribute 与格式 :format 不匹配。',
    'different'      => ':attribute 和 :other 必须不同。',
    'digits'         => ':attribute 必须是 :digits 位。',
    'digits_between' => ':attribute 必须介于 :min 和 :max 位之间。',
    'dimensions'     => ':attribute 的图像尺寸无效。',
    'distinct'       => ':attribute 字段具有重复值。',
    'email'          => ':attribute 必须是有效的电子邮件地址。',
    'exists'         => '所选的 :attribute 无效。',
    'exists-value'   => ':input 不存在。',
    'file'           => ':attribute 必须是文件。',
    'filled'         => ':attribute 字段必须具有值。',

    'gt' => [
        'numeric' => ':attribute 必须大于 :value。',
        'file'    => ':attribute 必须大于 :value 千字节。',
        'string'  => ':attribute 必须大于 :value 字符。',
        'array'   => ':attribute 必须包含多于 :value 个项目。',
    ],

    'gte' => [
        'numeric' => ':attribute 必须大于或等于 :value。',
        'file'    => ':attribute 必须大于或等于 :value 千字节。',
        'string'  => ':attribute 必须大于或等于 :value 字符。',
        'array'   => ':attribute 必须具有 :value 个或更多项。',
    ],

    'image'    => ':attribute 必须是图像。',
    'in'       => '所选的 :attribute 无效。',
    'in_array' => ':other 中不存在 :attribute 字段。',
    'integer'  => ':attribute 必须是整数。',
    'ip'       => ':attribute 必须是有效的 IP 地址。',
    'ipv4'     => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6'     => ':attribute 必须是有效的 IPv6 地址。',
    'json'     => ':attribute 必须是有效的 JSON 字符串。',

    'lt' => [
        'numeric' => ':attribute 必须小于 :value。',
        'file'    => ':attribute 必须小于 :value 千字节。',
        'string'  => ':attribute 必须小于 :value 字符。',
        'array'   => ':attribute 必须包含少于 :value 个项目。',
    ],

    'lte' => [
        'numeric' => ':attribute 必须小于或等于 :value。',
        'file'    => ':attribute 必须小于或等于 :value 千字节。',
        'string'  => ':attribute 必须小于或等于 :value 字符。',
        'array'   => ':attribute 不得包含超过 :value 个项目。',
    ],

    'max' => [
        'numeric' => ':attribute 不得大于 :max。',
        'file'    => ':attribute 不得大于 :max 千字节。',
        'string'  => ':attribute 不得大于 :max 字符。',
        'array'   => ':attribute 不得包含超过 :max 个项目。',
    ],

    'mimes'     => ':attribute 必须是类型为 :values 的文件。',
    'mimetypes' => ':attribute 必须是类型为 :values 的文件。',

    'min' => [
        'numeric' => ':attribute 必须至少为 :min。',
        'file'    => ':attribute 必须至少为 :min 千字节。',
        'string'  => ':attribute 必须至少为 :min 字符。',
        'array'   => ':attribute 必须至少包含 :min 项。',
    ],

    'not_in'               => '所选的 :attribute 无效。',
    'not_regex'            => ':attribute 格式无效。',
    'numeric'              => ':attribute 必须是数字。',
    'present'              => ':attribute 字段必须存在。',
    'regex'                => ':attribute 格式无效。',
    'required'             => ':attribute 字段是必填字段。',
    'required_if'          => '当 :other 为 :value 时，:attribute 字段是必填字段。',
    'required_unless'      => '除非 :other 在 :values 中，否则 :attribute 字段是必填字段。',
    'required_with'        => '当 :values 存在时，:attribute 字段是必需的。',
    'required_with_all'    => '当 :values 存在时，:attribute 字段是必需的。',
    'required_without'     => '当 :values 不存在时，:attribute 字段是必需的。',
    'required_without_all' => '当 :values 均不存在时，:attribute 字段是必需的。',
    'same'                 => ':attribute 和 :other 必须匹配。',

    'size' => [
        'numeric' => ':attribute 必须是 :size。',
        'file'    => ':attribute 必须是 :size 千字节。',
        'string'  => ':attribute 必须是 :size 字符。',
        'array'   => ':attribute 必须包含 :size 个项目。',
    ],

    'string'   => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效区域。',
    'unique'   => ':attribute 已被占用。',
    'uploaded' => ':attribute 上传失败。',
    'url'      => ':attribute 格式无效。',

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
            'rule-name' => '自定义消息',
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
