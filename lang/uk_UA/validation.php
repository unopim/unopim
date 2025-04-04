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
    'accepted'        => ':attribute має бути прийнятий.',
    'active_url'      => ':attribute не є дійсною URL-адресою.',
    'after'           => ':attribute має бути датою після :date.',
    'after_or_equal'  => ':attribute має бути датою після або рівною :date.',
    'alpha'           => ':attribute може містити лише літери.',
    'alpha_dash'      => ':attribute може містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num'       => ':attribute може містити лише літери та цифри.',
    'array'           => ':attribute має бути масивом.',
    'before'          => ':attribute має бути датою до :date.',
    'before_or_equal' => ':attribute має бути датою до або рівною :date.',

    'between' => [
        'numeric' => ':attribute має бути між :min і :max.',
        'file'    => ':attribute має бути між :min і :max кілобайтами.',
        'string'  => ':attribute має бути між :min і :max символами.',
        'array'   => ':attribute має містити від :min до :max елементів.',
    ],

    'boolean'        => ':attribute має бути або true, або false.',
    'confirmed'      => ':attribute підтвердження не співпадає.',
    'date'           => ':attribute не є дійсною датою.',
    'date_format'    => ':attribute не співпадає з форматом :format.',
    'different'      => ':attribute і :other повинні бути різними.',
    'digits'         => ':attribute має містити :digits цифр.',
    'digits_between' => ':attribute має містити від :min до :max цифр.',
    'dimensions'     => 'Розміри зображення для :attribute недійсні.',
    'distinct'       => ':attribute має повторюване значення.',
    'email'          => ':attribute має бути дійсною електронною адресою.',
    'exists'         => 'Вибраний :attribute недійсний.',
    'extensions'     => 'Поле :attribute повинно мати одне з наступних розширень: :values.',
    'file'           => ':attribute має бути файлом.',
    'filled'         => ':attribute має містити значення.',

    'gt' => [
        'numeric' => ':attribute має бути більшим за :value.',
        'file'    => ':attribute має бути більшим за :value кілобайтів.',
        'string'  => ':attribute має бути більшим за :value символів.',
        'array'   => ':attribute має містити більше ніж :value елементів.',
    ],

    'gte' => [
        'numeric' => ':attribute має бути більшим або рівним :value.',
        'file'    => ':attribute має бути більшим або рівним :value кілобайтів.',
        'string'  => ':attribute має бути більшим або рівним :value символів.',
        'array'   => ':attribute має містити не менше ніж :value елементів.',
    ],

    'image'    => ':attribute має бути зображенням.',
    'in'       => 'Вибраний :attribute недійсний.',
    'in_array' => ':attribute не існує в :other.',
    'integer'  => ':attribute має бути цілим числом.',
    'ip'       => ':attribute має бути дійсною IP-адресою.',
    'ipv4'     => ':attribute має бути дійсною IPv4-адресою.',
    'ipv6'     => ':attribute має бути дійсною IPv6-адресою.',
    'json'     => ':attribute має бути дійсним JSON рядком.',

    'lt' => [
        'numeric' => ':attribute має бути меншим за :value.',
        'file'    => ':attribute має бути меншим за :value кілобайтів.',
        'string'  => ':attribute має бути меншим за :value символів.',
        'array'   => ':attribute має містити менше ніж :value елементів.',
    ],

    'lte' => [
        'numeric' => ':attribute має бути меншим або рівним :value.',
        'file'    => ':attribute має бути меншим або рівним :value кілобайтів.',
        'string'  => ':attribute має бути меншим або рівним :value символів.',
        'array'   => ':attribute не може містити більше ніж :value елементів.',
    ],

    'max' => [
        'numeric' => ':attribute не може бути більшим ніж :max.',
        'file'    => ':attribute не може бути більшим ніж :max кілобайтів.',
        'string'  => ':attribute не може бути довшим ніж :max символів.',
        'array'   => ':attribute не може містити більше ніж :max елементів.',
    ],

    'mimes'     => ':attribute має бути файлом типу: :values.',
    'mimetypes' => ':attribute має бути файлом типу: :values.',

    'min' => [
        'numeric' => ':attribute має бути не менше ніж :min.',
        'file'    => ':attribute має бути не менше ніж :min кілобайтів.',
        'string'  => ':attribute має бути не менше ніж :min символів.',
        'array'   => ':attribute має містити не менше ніж :min елементів.',
    ],

    'not_in'               => 'Вибраний :attribute недійсний.',
    'not_regex'            => 'Формат :attribute недійсний.',
    'numeric'              => ':attribute має бути числом.',
    'present'              => 'Поле :attribute має бути присутнє.',
    'regex'                => 'Формат :attribute недійсний.',
    'required'             => 'Поле :attribute є обов\'язковим.',
    'required_if'          => 'Поле :attribute є обов\'язковим, коли :other є :value.',
    'required_unless'      => 'Поле :attribute є обов\'язковим, якщо тільки :other не є :values.',
    'required_with'        => 'Поле :attribute є обов\'язковим, коли :values присутнє.',
    'required_with_all'    => 'Поле :attribute є обов\'язковим, коли :values присутнє.',
    'required_without'     => 'Поле :attribute є обов\'язковим, коли :values відсутнє.',
    'required_without_all' => 'Поле :attribute є обов\'язковим, коли :values відсутні.',
    'same'                 => ':attribute і :other повинні співпадати.',

    'size' => [
        'numeric' => ':attribute має бути :size.',
        'file'    => ':attribute має бути :size кілобайтів.',
        'string'  => ':attribute має бути :size символів.',
        'array'   => ':attribute має містити :size елементів.',
    ],

    'string'   => ':attribute має бути рядком.',
    'timezone' => ':attribute має бути дійсним часовим поясом.',
    'unique'   => ':attribute вже зайнятий.',
    'uploaded' => ':attribute не вдалося завантажити.',
    'url'      => 'Формат :attribute недійсний.',

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
            'rule-name' => 'кастомне повідомлення',
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
