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
    'accepted'        => ':attribute debe ser aceptado.',
    'active_url'      => ':attribute no es una URL válida.',
    'after'           => ':attribute debe ser una fecha después de :date.',
    'after_or_equal'  => ':attribute debe ser una fecha igual o posterior a :date.',
    'alpha'           => ':attribute solo puede contener letras.',
    'alpha_dash'      => ':attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num'       => ':attribute solo puede contener letras y números.',
    'array'           => ':attribute debe ser un arreglo.',
    'before'          => ':attribute debe ser una fecha antes de :date.',
    'before_or_equal' => ':attribute debe ser una fecha igual o anterior a :date.',

    'between' => [
        'numeric' => ':attribute debe ser entre :min y :max.',
        'file'    => ':attribute debe ser entre :min y :max kilobytes.',
        'string'  => ':attribute debe tener entre :min y :max caracteres.',
        'array'   => ':attribute debe tener entre :min y :max elementos.',
    ],

    'boolean'        => ':attribute debe ser verdadero o falso.',
    'confirmed'      => ':attribute la confirmación no coincide.',
    'date'           => ':attribute no es una fecha válida.',
    'date_format'    => ':attribute no coincide con el formato :format.',
    'different'      => ':attribute y :other deben ser diferentes.',
    'digits'         => ':attribute debe ser :digits dígitos.',
    'digits_between' => ':attribute debe tener entre :min y :max dígitos.',
    'dimensions'     => 'Las dimensiones de la imagen de :attribute no son válidas.',
    'distinct'       => ':attribute tiene un valor duplicado.',
    'email'          => ':attribute debe ser una dirección de correo electrónico válida.',
    'exists'         => 'El :attribute seleccionado no es válido.',
    'extensions'     => 'El campo :attribute debe tener una de las siguientes extensiones: :values.',
    'file'           => ':attribute debe ser un archivo.',
    'filled'         => ':attribute debe tener un valor.',

    'gt' => [
        'numeric' => ':attribute debe ser mayor que :value.',
        'file'    => ':attribute debe ser mayor que :value kilobytes.',
        'string'  => ':attribute debe ser mayor que :value caracteres.',
        'array'   => ':attribute debe tener más de :value elementos.',
    ],

    'gte' => [
        'numeric' => ':attribute debe ser mayor o igual que :value.',
        'file'    => ':attribute debe ser mayor o igual que :value kilobytes.',
        'string'  => ':attribute debe ser mayor o igual que :value caracteres.',
        'array'   => ':attribute debe tener al menos :value elementos.',
    ],

    'image'    => ':attribute debe ser una imagen.',
    'in'       => ':attribute seleccionado no es válido.',
    'in_array' => ':attribute no existe en :other.',
    'integer'  => ':attribute debe ser un número entero.',
    'ip'       => ':attribute debe ser una dirección IP válida.',
    'ipv4'     => ':attribute debe ser una dirección IPv4 válida.',
    'ipv6'     => ':attribute debe ser una dirección IPv6 válida.',
    'json'     => ':attribute debe ser una cadena JSON válida.',

    'lt' => [
        'numeric' => ':attribute debe ser menor que :value.',
        'file'    => ':attribute debe ser menor que :value kilobytes.',
        'string'  => ':attribute debe ser menor que :value caracteres.',
        'array'   => ':attribute debe tener menos de :value elementos.',
    ],

    'lte' => [
        'numeric' => ':attribute debe ser menor o igual que :value.',
        'file'    => ':attribute debe ser menor o igual que :value kilobytes.',
        'string'  => ':attribute debe ser menor o igual que :value caracteres.',
        'array'   => ':attribute no puede tener más de :value elementos.',
    ],

    'max' => [
        'numeric' => ':attribute no debe ser mayor que :max.',
        'file'    => ':attribute no debe ser mayor que :max kilobytes.',
        'string'  => ':attribute no debe ser mayor que :max caracteres.',
        'array'   => ':attribute no debe tener más de :max elementos.',
    ],

    'mimes'     => ':attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => ':attribute debe ser un archivo de tipo: :values.',

    'min' => [
        'numeric' => ':attribute debe ser al menos :min.',
        'file'    => ':attribute debe ser al menos :min kilobytes.',
        'string'  => ':attribute debe tener al menos :min caracteres.',
        'array'   => ':attribute debe tener al menos :min elementos.',
    ],

    'not_in'               => ':attribute seleccionado no es válido.',
    'not_regex'            => 'El formato de :attribute no es válido.',
    'numeric'              => ':attribute debe ser un número.',
    'present'              => ':attribute debe estar presente.',
    'regex'                => 'El formato de :attribute no es válido.',
    'required'             => ':attribute es obligatorio.',
    'required_if'          => ':attribute es obligatorio cuando :other es :value.',
    'required_unless'      => ':attribute es obligatorio a menos que :other sea :values.',
    'required_with'        => ':attribute es obligatorio cuando :values está presente.',
    'required_with_all'    => ':attribute es obligatorio cuando :values están presentes.',
    'required_without'     => ':attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => ':attribute es obligatorio cuando :values no está presente.',
    'same'                 => ':attribute y :other deben coincidir.',

    'size' => [
        'numeric' => ':attribute debe ser :size.',
        'file'    => ':attribute debe ser :size kilobytes.',
        'string'  => ':attribute debe ser :size caracteres.',
        'array'   => ':attribute debe tener :size elementos.',
    ],

    'string'   => ':attribute debe ser una cadena de texto.',
    'timezone' => ':attribute debe ser una zona horaria válida.',
    'unique'   => ':attribute ya ha sido registrado.',
    'uploaded' => ':attribute no se ha podido subir.',
    'url'      => ':attribute no tiene un formato válido.',

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
            'rule-name' => 'mensaje personalizado',
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
