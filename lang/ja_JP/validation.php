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
    'accepted'        => ':attribute は受け入れられる必要があります。',
    'active_url'      => ':attribute は有効な URL ではありません。',
    'after'           => ':attribute は :date より後の日付である必要があります。',
    'after_or_equal'  => ':attribute は :date より後の日付である必要があります。',
    'alpha'           => ':attribute には文字のみを含めることができます。',
    'alpha_dash'      => ':attribute には文字、数字、ダッシュ、およびアンダースコアのみを含めることができます。',
    'alpha_num'       => ':attribute には文字と数字のみを含めることができます。',
    'array'           => ':attribute は配列である必要があります。',
    'before'          => ':attribute は :date より前の日付である必要があります。',
    'before_or_equal' => ':attribute は :date より前の日付である必要があります。',

    'between' => [
        'numeric' => ':attribute は :min から :max までの範囲でなければなりません。',
        'file'    => ':attribute は :min から :max までの範囲でなければなりません。',
        'string'  => ':attribute は :min から :max までの範囲でなければなりません。',
        'array'   => ':attribute には :min から :max までの範囲の項目が必要です。',
    ],

    'boolean'        => ':attribute フィールドは true または false である必要があります。',
    'confirmed'      => ':attribute の確認が一致しません。',
    'date'           => ':attribute は有効な日付ではありません。',
    'date_format'    => ':attribute は形式 :format と一致しません。',
    'different'      => ':attribute と :other は異なる必要があります。',
    'digits'         => ':attribute は :digits 桁である必要があります。',
    'digits_between' => ':attribute は :min 桁から :max 桁までの範囲である必要があります。',
    'dimensions'     => ':attribute に無効な画像サイズがあります。',
    'distinct'       => ':attribute フィールドに重複した値があります。',
    'email'          => ':attribute は有効なメール アドレスである必要があります。',
    'exists'         => '選択した :attribute は無効です。',
    'exists-value'   => ':input が存在しません。',
    'extensions'     => ':attribute フィールドには次のいずかの拡張子が必要です: :values.',
    'file'           => ':attribute はファイルである必要があります。',
    'filled'         => ':attribute フィールドには値が必要です。',

    'gt' => [
        'numeric' => ':attribute は :value より大きくなければなりません。',
        'file'    => ':attribute は :value キロバイトより大きくなければなりません。',
        'string'  => ':attribute は :value 文字より大きくなければなりません。',
        'array'   => ':attribute には :value 個以上の項目が必要です。',
    ],

    'gte' => [
        'numeric' => ':attribute は :value 以上である必要があります。',
        'file'    => ':attribute は :value キロバイト以上である必要があります。',
        'string'  => ':attribute は :value 文字以上である必要があります。',
        'array'   => ':attribute には :value 項目以上が必要です。',
    ],

    'image'    => ':attribute は画像である必要があります。',
    'in'       => '選択された :attribute は無効です。',
    'in_array' => ':attribute フィールドは :other に存在しません。',
    'integer'  => ':attribute は整数である必要があります。',
    'ip'       => ':attribute は有効な IP アドレスである必要があります。',
    'ipv4'     => ':attribute は有効な IPv4 アドレスである必要があります。',
    'ipv6'     => ':attribute は有効な IPv6 アドレスである必要があります。',
    'json'     => ':attribute は有効な JSON 文字列である必要があります。',

    'lt' => [
        'numeric' => ':attribute は :value 未満である必要があります。',
        'file'    => ':attribute は :value キロバイト未満である必要があります。',
        'string'  => ':attribute は :value 文字未満である必要があります。',
        'array'   => ':attribute には :value 項目未満が必要です。',
    ],

    'lte' => [
        'numeric' => ':attribute は :value 以下である必要があります。',
        'file'    => ':attribute は :value キロバイト以下である必要があります。',
        'string'  => ':attribute は :value 文字以下である必要があります。',
        'array'   => ':attribute には :value を超える項目を含めることはできません。',
    ],

    'max' => [
        'numeric' => ':attribute は :max より大きくできません。',
        'file'    => ':attribute は :max キロバイトより大きくできません。',
        'string'  => ':attribute は :max 文字より大きくできません。',
        'array'   => ':attribute には :max 項目より多く指定できません。',
    ],

    'mimes'     => ':attribute はタイプ: :values のファイルである必要があります。',
    'mimetypes' => ':attribute はタイプ: :values のファイルである必要があります。',

    'min' => [
        'numeric' => ':attribute は少なくとも :min である必要があります。',
        'file'    => ':attribute は少なくとも :min キロバイトである必要があります。',
        'string'  => ':attribute は少なくとも :min 文字である必要があります。',
        'array'   => ':attribute には少なくとも :min 項目が必要です。',
    ],

    'not_in'               => '選択された :attribute は無効です。',
    'not_regex'            => ':attribute の形式が無効です。',
    'numeric'              => ':attribute は数値でなければなりません。',
    'present'              => ':attribute フィールドが存在する必要があります。',
    'regex'                => ':attribute の形式が無効です。',
    'required'             => ':attribute フィールドは必須です。',
    'required_if'          => ':other が :value の場合、:attribute フィールドは必須です。',
    'required_unless'      => ':other が :values にない場合、:attribute フィールドは必須です。',
    'required_with'        => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_with_all'    => ':values が存在する場合、:attribute フィールドは必須です。',
    'required_without'     => ':values が存在しない場合、:attribute フィールドは必須です。',
    'required_without_all' => ':values がいずれも存在しない場合、:attribute フィールドは必須です。',
    'same'                 => ':attribute と :other は一致する必要があります。',

    'size' => [
        'numeric' => ':attribute は :size である必要があります。',
        'file'    => ':attribute は :size キロバイトである必要があります。',
        'string'  => ':attribute は :size 文字である必要があります。',
        'array'   => ':attribute には :size 個の項目が含まれている必要があります。',
    ],

    'string'   => ':attribute は文字列である必要があります。',
    'timezone' => ':attribute は有効なゾーンである必要があります。',
    'unique'   => ':attribute はすでに使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'url'      => ':attribute の形式が無効です。',

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
            'rule-name' => 'カスタムメッセージ',
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
