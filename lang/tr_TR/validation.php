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
    'accepted'        => ':attribute kabul edilmelidir.',
    'active_url'      => ':attribute geçerli bir URL değildir.',
    'after'           => ':attribute, :date tarihinden sonra olmalıdır.',
    'after_or_equal'  => ':attribute, :date tarihinden sonra veya ona eşit olmalıdır.',
    'alpha'           => ':attribute sadece harf içerebilir.',
    'alpha_dash'      => ':attribute sadece harf, rakam, tire ve alt çizgi içerebilir.',
    'alpha_num'       => ':attribute sadece harf ve rakam içerebilir.',
    'array'           => ':attribute bir dizi olmalıdır.',
    'before'          => ':attribute, :date tarihinden önce olmalıdır.',
    'before_or_equal' => ':attribute, :date tarihinden önce veya ona eşit olmalıdır.',

    'between' => [
        'numeric' => ':attribute, :min ile :max arasında olmalıdır.',
        'file'    => ':attribute, :min ile :max kilobayt arasında olmalıdır.',
        'string'  => ':attribute, :min ile :max karakter arasında olmalıdır.',
        'array'   => ':attribute, :min ile :max öğe arasında olmalıdır.',
    ],

    'boolean'        => ':attribute doğru veya yanlış olmalıdır.',
    'confirmed'      => ':attribute onayı eşleşmiyor.',
    'date'           => ':attribute geçerli bir tarih değildir.',
    'date_format'    => ':attribute, :format formatı ile eşleşmiyor.',
    'different'      => ':attribute ve :other farklı olmalıdır.',
    'digits'         => ':attribute, :digits haneli olmalıdır.',
    'digits_between' => ':attribute, :min ile :max arasında haneli olmalıdır.',
    'dimensions'     => ':attribute görüntü boyutları geçersiz.',
    'distinct'       => ':attribute, yinelenen bir değere sahiptir.',
    'email'          => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'exists'         => 'Seçilen :attribute geçersiz.',
    'file'           => ':attribute bir dosya olmalıdır.',
    'filled'         => ':attribute bir değer içermelidir.',

    'gt' => [
        'numeric' => ':attribute, :value\'dan büyük olmalıdır.',
        'file'    => ':attribute, :value kilobayt\'tan büyük olmalıdır.',
        'string'  => ':attribute, :value karakterden büyük olmalıdır.',
        'array'   => ':attribute, :value öğeden fazla olmalıdır.',
    ],

    'gte' => [
        'numeric' => ':attribute, :value\'dan büyük veya eşit olmalıdır.',
        'file'    => ':attribute, :value kilobayt\'tan büyük veya eşit olmalıdır.',
        'string'  => ':attribute, :value karakterden büyük veya eşit olmalıdır.',
        'array'   => ':attribute, en az :value öğeye sahip olmalıdır.',
    ],

    'image'    => ':attribute bir resim olmalıdır.',
    'in'       => 'Seçilen :attribute geçersiz.',
    'in_array' => ':attribute, :other içinde mevcut değil.',
    'integer'  => ':attribute bir tamsayı olmalıdır.',
    'ip'       => ':attribute geçerli bir IP adresi olmalıdır.',
    'ipv4'     => ':attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6'     => ':attribute geçerli bir IPv6 adresi olmalıdır.',
    'json'     => ':attribute geçerli bir JSON dizesi olmalıdır.',

    'lt' => [
        'numeric' => ':attribute, :value\'dan küçük olmalıdır.',
        'file'    => ':attribute, :value kilobayt\'tan küçük olmalıdır.',
        'string'  => ':attribute, :value karakterden küçük olmalıdır.',
        'array'   => ':attribute, :value öğeden az olmalıdır.',
    ],

    'lte' => [
        'numeric' => ':attribute, :value\'dan küçük veya eşit olmalıdır.',
        'file'    => ':attribute, :value kilobayt\'tan küçük veya eşit olmalıdır.',
        'string'  => ':attribute, :value karakterden küçük veya eşit olmalıdır.',
        'array'   => ':attribute, :value öğeden fazla olmamalıdır.',
    ],

    'max' => [
        'numeric' => ':attribute, :max\'dan büyük olmamalıdır.',
        'file'    => ':attribute, :max kilobayt\'tan büyük olmamalıdır.',
        'string'  => ':attribute, :max karakterden uzun olmamalıdır.',
        'array'   => ':attribute, :max öğeden fazla olmamalıdır.',
    ],

    'mimes'     => ':attribute, şu dosya türlerinden biri olmalıdır: :values.',
    'mimetypes' => ':attribute, şu dosya türlerinden biri olmalıdır: :values.',

    'min' => [
        'numeric' => ':attribute, en az :min olmalıdır.',
        'file'    => ':attribute, en az :min kilobayt olmalıdır.',
        'string'  => ':attribute, en az :min karakter olmalıdır.',
        'array'   => ':attribute, en az :min öğeye sahip olmalıdır.',
    ],

    'not_in'               => 'Seçilen :attribute geçersiz.',
    'not_regex'            => ':attribute formatı geçersiz.',
    'numeric'              => ':attribute bir sayı olmalıdır.',
    'present'              => ':attribute alanı mevcut olmalıdır.',
    'regex'                => ':attribute formatı geçersiz.',
    'required'             => ':attribute alanı gereklidir.',
    'required_if'          => ':attribute alanı, :other :value olduğu zaman gereklidir.',
    'required_unless'      => ':attribute alanı, :other :values dışında gereklidir.',
    'required_with'        => ':attribute alanı, :values mevcut olduğunda gereklidir.',
    'required_with_all'    => ':attribute alanı, :values mevcut olduğunda gereklidir.',
    'required_without'     => ':attribute alanı, :values mevcut olmadığında gereklidir.',
    'required_without_all' => ':attribute alanı, :values hiçbiri mevcut olmadığında gereklidir.',
    'same'                 => ':attribute ve :other aynı olmalıdır.',

    'size' => [
        'numeric' => ':attribute, :size olmalıdır.',
        'file'    => ':attribute, :size kilobayt olmalıdır.',
        'string'  => ':attribute, :size karakter olmalıdır.',
        'array'   => ':attribute, :size öğeye sahip olmalıdır.',
    ],

    'string'   => ':attribute bir dize olmalıdır.',
    'timezone' => ':attribute geçerli bir zaman dilimi olmalıdır.',
    'unique'   => ':attribute zaten alınmış.',
    'uploaded' => ':attribute yüklenemedi.',
    'url'      => ':attribute formatı geçersiz.',

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
            'rule-name' => 'özel mesaj',
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
