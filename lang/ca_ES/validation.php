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
    'accepted'        => 'El :attribute s\'ha d\'acceptar.',
    'active_url'      => 'El :attribute no és una URL vàlida.',
    'after'           => 'El :attribute ha de ser una data posterior a :date.',
    'after_or_equal'  => 'El :attribute ha de ser una data posterior o igual a :date.',
    'alpha'           => 'El :attribute només pot contenir lletres.',
    'alpha_dash'      => 'El :attribute només pot contenir lletres, números, guions i guions baixos.',
    'alpha_num'       => 'El :attribute només pot contenir lletres i números.',
    'array'           => 'El :attribute ha de ser un array.',
    'before'          => 'El :attribute ha de ser una data anterior a :date.',
    'before_or_equal' => 'El :attribute ha de ser una data anterior o igual a :date.',

    'between' => [
        'numeric' => 'El :attribute ha de ser entre :min i :max.',
        'file'    => 'El :attribute ha de ser entre :min i :max kilobytes.',
        'string'  => 'El :attribute ha de ser entre :min i :max caràcters.',
        'array'   => 'El :attribute ha de tenir entre :min i :max elements.',
    ],

    'boolean'        => 'El camp :attribute ha de ser cert o fals.',
    'confirmed'      => 'La confirmació de :attribute no coincideix.',
    'date'           => 'El :attribute no és una data vàlida.',
    'date_format'    => 'El :attribute no coincideix amb el format :format.',
    'different'      => 'El :attribute i :other han de ser diferents.',
    'digits'         => 'El :attribute ha de ser :digits dígits.',
    'digits_between' => 'El :attribute ha de ser entre :min i :max dígits.',
    'dimensions'     => 'El :attribute té dimensions d\'imatge no vàlides.',
    'distinct'       => 'El camp :attribute té un valor duplicat.',
    'email'          => 'El :attribute ha de ser una adreça de correu electrònic vàlida.',
    'exists'         => 'L\'atribut seleccionat :attribute no és vàlid.',
    'exists-value'   => 'El :input no existeix.',
    'file'           => 'El :attribute ha de ser un arxiu.',
    'filled'         => 'El camp :attribute ha de tenir un valor.',

    'gt' => [
        'numeric' => 'El :attribute ha de ser major que :value.',
        'file'    => 'El :attribute ha de ser major que :value kilobytes.',
        'string'  => 'El :attribute ha de ser més llarg que :value caràcters.',
        'array'   => 'El :attribute ha de tenir més de :value elements.',
    ],

    'gte' => [
        'numeric' => 'El :attribute ha de ser major o igual a :value.',
        'file'    => 'El :attribute ha de ser major o igual a :value kilobytes.',
        'string'  => 'El :attribute ha de ser més llarg o igual a :value caràcters.',
        'array'   => 'El :attribute ha de tenir com a mínim :value elements.',
    ],

    'image'    => 'El :attribute ha de ser una imatge.',
    'in'       => 'L\'atribut seleccionat :attribute no és vàlid.',
    'in_array' => 'El camp :attribute no existeix en :other.',
    'integer'  => 'El :attribute ha de ser un enter.',
    'ip'       => 'El :attribute ha de ser una adreça IP vàlida.',
    'ipv4'     => 'El :attribute ha de ser una adreça IPv4 vàlida.',
    'ipv6'     => 'El :attribute ha de ser una adreça IPv6 vàlida.',
    'json'     => 'El :attribute ha de ser una cadena JSON vàlida.',

    'lt' => [
        'numeric' => 'El :attribute ha de ser menor que :value.',
        'file'    => 'El :attribute ha de ser menor que :value kilobytes.',
        'string'  => 'El :attribute ha de ser més curt que :value caràcters.',
        'array'   => 'El :attribute ha de tenir menys de :value elements.',
    ],

    'lte' => [
        'numeric' => 'El :attribute ha de ser menor o igual a :value.',
        'file'    => 'El :attribute ha de ser menor o igual a :value kilobytes.',
        'string'  => 'El :attribute ha de ser més curt o igual a :value caràcters.',
        'array'   => 'El :attribute no ha de tenir més de :value elements.',
    ],

    'max' => [
        'numeric' => 'El :attribute no pot ser més gran que :max.',
        'file'    => 'El :attribute no pot ser més gran que :max kilobytes.',
        'string'  => 'El :attribute no pot tenir més de :max caràcters.',
        'array'   => 'El :attribute no pot tenir més de :max elements.',
    ],

    'mimes'     => 'El :attribute ha de ser un arxiu del tipus: :values.',
    'mimetypes' => 'El :attribute ha de ser un arxiu del tipus: :values.',

    'min' => [
        'numeric' => 'El :attribute ha de ser almenys :min.',
        'file'    => 'El :attribute ha de ser almenys :min kilobytes.',
        'string'  => 'El :attribute ha de tenir almenys :min caràcters.',
        'array'   => 'El :attribute ha de tenir almenys :min elements.',
    ],

    'not_in'               => 'L\'atribut seleccionat :attribute no és vàlid.',
    'not_regex'            => 'El format de :attribute no és vàlid.',
    'numeric'              => 'El :attribute ha de ser un número.',
    'present'              => 'El camp :attribute ha de estar present.',
    'regex'                => 'El format de :attribute no és vàlid.',
    'required'             => 'El camp :attribute és obligatori.',
    'required_if'          => 'El camp :attribute és obligatori quan :other és :value.',
    'required_unless'      => 'El camp :attribute és obligatori a menys que :other estigui en :values.',
    'required_with'        => 'El camp :attribute és obligatori quan :values estigui present.',
    'required_with_all'    => 'El camp :attribute és obligatori quan :values estigui present.',
    'required_without'     => 'El camp :attribute és obligatori quan :values no estigui present.',
    'required_without_all' => 'El camp :attribute és obligatori quan cap dels :values estigui present.',
    'same'                 => 'El :attribute i :other han de coincidir.',

    'size' => [
        'numeric' => 'El :attribute ha de ser :size.',
        'file'    => 'El :attribute ha de ser :size kilobytes.',
        'string'  => 'El :attribute ha de tenir :size caràcters.',
        'array'   => 'El :attribute ha de contenir :size elements.',
    ],

    'string'   => 'El :attribute ha de ser una cadena.',
    'timezone' => 'El :attribute ha de ser una zona vàlida.',
    'unique'   => 'El :attribute ja ha estat pres.',
    'uploaded' => 'El :attribute ha fallat a l\'hora d\'enviar.',
    'url'      => 'El format de :attribute no és vàlid.',

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
            'rule-name' => 'missatge personalitzat',
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
