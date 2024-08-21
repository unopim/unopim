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
    'accepted'        => ':attribute должен быть принят.',
    'active_url'      => ':attribute не является допустимым URL.',
    'after'           => ':attribute должен быть датой после :date.',
    'after_or_equal'  => ':attribute должен быть датой после или равной :date.',
    'alpha'           => ':attribute может содержать только буквы.',
    'alpha_dash'      => ':attribute может содержать только буквы, цифры, тире и подчеркивания.',
    'alpha_num'       => ':attribute может содержать только буквы и цифры.',
    'array'           => ':attribute должен быть массивом.',
    'before'          => ':attribute должен быть датой до :date.',
    'before_or_equal' => ':attribute должен быть датой до или равной :date.',

    'between' => [
        'numeric' => ':attribute должен быть между :min и :max.',
        'file'    => ':attribute должен быть между :min и :max килобайт.',
        'string'  => ':attribute должен быть между :min и :max символов.',
        'array'   => ':attribute должен иметь между :min и :max элементов.',
    ],

    'boolean'        => 'Поле :attribute должно быть true или false.',
    'confirmed'      => 'Подтверждение :attribute не совпадает.',
    'date'           => ':attribute не является допустимой датой.',
    'date_format'    => ':attribute не соответствует формату :format.',
    'different'      => ':attribute и :other должны быть разными.',
    'digits'         => ':attribute должен быть :digits цифр.',
    'digits_between' => ':attribute должен быть между :min и :max цифр.',
    'dimensions'     => ':attribute имеет недопустимые размеры изображения.',
    'distinct'       => 'Поле :attribute имеет дублирующееся значение.',
    'email'          => ':attribute должен быть допустимым адресом электронной почты.',
    'exists'         => 'Выбранный :attribute недопустим.',
    'exists-value'   => ':input не существует.',
    'file'           => ':attribute должен быть файлом.',
    'filled'         => 'Поле :attribute должно иметь значение.',

    'gt' => [
        'numeric' => ':attribute должен быть больше, чем :value.',
        'file'    => ':attribute должен быть больше, чем :value килобайт.',
        'string'  => ':attribute должен быть больше, чем :value символов.',
        'array'   => ':attribute должен иметь больше, чем :value элементов.',
    ],

    'gte' => [
        'numeric' => ':attribute должен быть больше или равен :value.',
        'file'    => ':attribute должен быть больше или равен :value килобайт.',
        'string'  => ':attribute должен быть больше или равен :value символов.',
        'array'   => ':attribute должен иметь :value элементов или более.',
    ],

    'image'    => ':attribute должен быть изображением.',
    'in'       => 'Выбранный :attribute недействителен.',
    'in_array' => 'Поле :attribute не существует в :other.',
    'integer'  => ':attribute должен быть целым числом.',
    'ip'       => ':attribute должен быть допустимым IP-адресом.',
    'ipv4'     => ':attribute должен быть допустимым адресом IPv4.',
    'ipv6'     => ':attribute должен быть допустимым адресом IPv6.',
    'json'     => ':attribute должен быть допустимой строкой JSON.',

    'lt' => [
        'numeric' => ':attribute должен быть меньше :value.',
        'file'    => ':attribute должен быть меньше :value килобайт.',
        'string'  => ':attribute должен быть меньше :value символов.',
        'array'   => ':attribute должен иметь меньше :value элементов.',
    ],

    'lte' => [
        'numeric' => ':attribute должен быть меньше или равен :value.',
        'file'    => ':attribute должен быть меньше или равен :value килобайт.',
        'string'  => ':attribute должен быть меньше или равен :value символов.',
        'array'   => ':attribute не должен иметь больше :value элементов.',
    ],

    'max' => [
        'numeric' => ':attribute не может быть больше :max.',
        'file'    => ':attribute не может быть больше :max килобайт.',
        'string'  => ':attribute не может быть больше :max символов.',
        'array'   => ':attribute не может иметь больше :max элементов.',
    ],

    'mimes'     => ':attribute должен быть файлом типа: :values.',
    'mimetypes' => ':attribute должен быть файлом типа: :values.',

    'min' => [
        'numeric' => ':attribute должен быть не менее :min.',
        'file'    => ':attribute должен быть не менее :min килобайт.',
        'string'  => ':attribute должен быть не менее :min символов.',
        'array'   => ':attribute должен иметь не менее :min элементов.',
    ],

    'not_in'               => 'Выбранный :attribute недействителен.',
    'not_regex'            => 'Формат :attribute недействителен.',
    'numeric'              => ':attribute должен быть числом.',
    'present'              => 'Поле :attribute должно присутствовать.',
    'regex'                => 'Формат :attribute недействителен.',
    'required'             => 'Поле :attribute обязательно.',
    'required_if'          => 'Поле :attribute обязательно, если :other — это :value.',
    'required_unless'      => 'Поле :attribute обязательно, если :other не находится в :values.',
    'required_with'        => 'Поле :attribute обязательно, если присутствует :values.',
    'required_with_all'    => 'Поле :attribute обязательно, если присутствует :values.',
    'required_without'     => 'Поле :attribute обязательно, если отсутствует :values.',
    'required_without_all' => 'Поле :attribute обязательно, если отсутствует ни одно из :values.',
    'same'                 => ':attribute и :other должны совпадать.',

    'size' => [
        'numeric' => ':attribute должен быть :size.',
        'file'    => ':attribute должен быть :size килобайт.',
        'string'  => ':attribute должен быть :size символов.',
        'array'   => ':attribute должен содержать :size элементов.',
    ],

    'string'   => ':attribute должен быть строкой.',
    'timezone' => ':attribute должен быть допустимой зоной.',
    'unique'   => ':attribute уже занят.',
    'uploaded' => ':attribute не удалось загрузить.',
    'url'      => 'Формат :attribute недействителен.',

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
            'rule-name' => 'пользовательское сообщение',
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
