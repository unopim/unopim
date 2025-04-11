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
    'accepted'        => 'El :attribute : debe ser aceptado.',
    'active_url'      => 'El :attribute : no es una URL válida.',
    'after'           => 'El :attribute : debe ser una fecha posterior a :date.',
    'after_or_equal'  => 'El :attribute : debe ser una fecha posterior o igual a :date.',
    'alpha'           => 'El :attribute : solo puede contener letras.',
    'alpha_dash'      => 'El :attribute : solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num'       => 'El :attribute : solo puede contener letras y números.',
    'array'           => 'El :attribute : debe ser una matriz.',
    'before'          => 'El :attribute : debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El :attribute : debe ser una fecha anterior o igual a :date.',

    'between' => [
        'numeric' => 'El :attribute : debe estar entre :min y :max.',
        'file'    => 'El :attribute : debe estar entre :min y :max kilobytes.',
        'string'  => 'El :attribute : debe estar entre :min y :max caracteres.',
        'array'   => 'El :attribute : debe tener entre :min y :max elementos.',
    ],

    'boolean'        => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed'      => 'La confirmación :attribute no coincide.',
    'date'           => 'El :attribute no es una fecha válida.',
    'date_format'    => 'El :attribute no coincide con el formato :format.',
    'different'      => 'El :attribute y :other deben ser diferentes.',
    'digits'         => 'El :attribute debe ser :digits dígitos.',
    'digits_between' => 'El :attribute debe estar entre :min y :max dígitos.',
    'dimensions'     => 'El :attribute : tiene dimensiones de imagen no válidas.',
    'distinct'       => 'El campo :attribute tiene un valor duplicado.',
    'email'          => 'El :attribute : debe ser una dirección de correo electrónico válida.',
    'exists'         => 'El :attribute : seleccionado no es válido.',
    'exists-value'   => 'La :input no existe.',
    'extensions'     => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file'           => 'El :attribute : debe ser un archivo.',
    'filled'         => 'El campo :attribute debe tener un valor.',

    'gt' => [
        'numeric' => 'El :attribute : debe ser mayor que :value.',
        'file'    => 'El :attribute : debe ser mayor que :value kilobytes.',
        'string'  => 'El :attribute : debe ser mayor que :value caracteres.',
        'array'   => 'El :attribute : debe tener más de :value elementos.',
    ],

    'gte' => [
        'numeric' => 'El :attribute : debe ser mayor o igual que :value.',
        'file'    => 'El :attribute : debe ser mayor o igual que :value kilobytes.',
        'string'  => 'El :attribute : debe ser mayor o igual que :value caracteres.',
        'array'   => 'El :attribute : debe tener :value elementos o más.',
    ],

    'image'    => 'El :attribute : debe ser una imagen.',
    'in'       => 'El :attribute seleccionado no es válido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer'  => 'El :attribute debe ser un entero.',
    'ip'       => 'El :attribute debe ser una dirección IP válida.',
    'ipv4'     => 'El :attribute debe ser una dirección IPv4 válida.',
    'ipv6'     => 'El :attribute debe ser una dirección IPv6 válida.',
    'json'     => 'El :attribute debe ser una cadena JSON válida.',

    'lt' => [
        'numeric' => 'El :attribute debe ser menor que :value.',
        'file'    => 'El :attribute debe ser menor que :value kilobytes.',
        'string'  => 'El :attribute debe ser menor que :value caracteres.',
        'array'   => 'El :attribute debe tener menos de :value elementos.',
    ],

    'lte' => [
        'numeric' => 'El :attribute debe ser menor o igual que :value.',
        'file'    => 'El :attribute debe ser menor o igual que :value kilobytes.',
        'string'  => 'El :attribute debe ser menor o igual que :value caracteres.',
        'array'   => 'El :attribute no debe tener más de :value elementos.',
    ],

    'max' => [
        'numeric' => 'El :attribute no puede ser mayor que :max.',
        'file'    => 'El :attribute no puede ser mayor que :max kilobytes.',
        'string'  => 'El :attribute no puede ser mayor que :max caracteres.',
        'array'   => 'El :attribute no puede tener más de :max elementos.',
    ],

    'mimes'     => 'El :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El :attribute debe ser un archivo de tipo: :values.',

    'min' => [
        'numeric' => 'El :attribute debe tener al menos :min.',
        'file'    => 'El :attribute debe tener al menos :min kilobytes.',
        'string'  => 'El :attribute debe tener al menos :min caracteres.',
        'array'   => 'El :attribute debe tener al menos :min elementos.',
    ],

    'not_in'               => 'El :attribute seleccionado no es válido.',
    'not_regex'            => 'El formato :attribute no es válido.',
    'numeric'              => 'El :attribute debe ser un número.',
    'present'              => 'El campo :attribute debe estar presente.',
    'regex'                => 'El formato :attribute no es válido.',
    'required'             => 'El campo :attribute es obligatorio.',
    'required_if'          => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_unless'      => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with'        => 'El campo :attribute es obligatorio cuando :values ​​está presente.',
    'required_with_all'    => 'El campo :attribute es obligatorio cuando :values ​​está presente.',
    'required_without'     => 'El campo :attribute es obligatorio cuando :values ​​no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de los :values ​​está presente.',
    'same'                 => 'El :attribute y :other deben coincidir.',

    'size' => [
        'numeric' => 'El :attribute debe ser :size.',
        'file'    => 'El :attribute debe ser :size kilobytes.',
        'string'  => 'El :attribute debe ser :size caracteres.',
        'array'   => 'El :attribute : debe contener elementos :size.',
    ],

    'string'   => 'El :attribute : debe ser una cadena.',
    'timezone' => 'El :attribute : debe ser una zona válida.',
    'unique'   => 'El :attribute : ya se ha utilizado.',
    'uploaded' => 'El :attribute : no se pudo cargar.',
    'url'      => 'El formato del :attribute : no es válido.',

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
            'rule-name' => 'mensaje-personalizado',
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
