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
    'accepted'        => ':attribute trebuie să fie acceptat.',
    'active_url'      => ':attribute nu este un URL valid.',
    'after'           => ':attribute trebuie să fie o dată ulterioară :date.',
    'after_or_equal'  => ':attribute trebuie să fie o dată ulterioară sau egală cu :date.',
    'alpha'           => ':attribute poate conține doar litere.',
    'alpha_dash'      => ':attribute poate conține doar litere, numere, cratime și liniuțe de subliniere.',
    'alpha_num'       => ':attribute poate conține doar litere și numere.',
    'array'           => ':attribute trebuie să fie un array.',
    'before'          => ':attribute trebuie să fie o dată anterioară :date.',
    'before_or_equal' => ':attribute trebuie să fie o dată anterioară sau egală cu :date.',

    'between' => [
        'numeric' => ':attribute trebuie să fie între :min și :max.',
        'file'    => ':attribute trebuie să fie între :min și :max kilobytes.',
        'string'  => ':attribute trebuie să fie între :min și :max caractere.',
        'array'   => ':attribute trebuie să aibă între :min și :max elemente.',
    ],

    'boolean'        => ':attribute trebuie să fie adevărat sau fals.',
    'confirmed'      => 'Confirmarea pentru :attribute nu se potrivește.',
    'date'           => ':attribute nu este o dată validă.',
    'date_format'    => ':attribute nu se potrivește cu formatul :format.',
    'different'      => ':attribute și :other trebuie să fie diferite.',
    'digits'         => ':attribute trebuie să aibă :digits cifre.',
    'digits_between' => ':attribute trebuie să aibă între :min și :max cifre.',
    'dimensions'     => 'Dimensiunile imaginii pentru :attribute nu sunt valide.',
    'distinct'       => ':attribute are o valoare duplicată.',
    'email'          => ':attribute trebuie să fie o adresă de e-mail validă.',
    'exists'         => ':attribute selectat este invalid.',
    'extensions'     => 'Câmpul :attribute trebuie să aibă una dintre următoarele extensii: :values.',
    'file'           => ':attribute trebuie să fie un fișier.',
    'filled'         => ':attribute trebuie să aibă o valoare.',

    'gt' => [
        'numeric' => ':attribute trebuie să fie mai mare decât :value.',
        'file'    => ':attribute trebuie să fie mai mare decât :value kilobytes.',
        'string'  => ':attribute trebuie să fie mai mare decât :value caractere.',
        'array'   => ':attribute trebuie să aibă mai mult de :value elemente.',
    ],

    'gte' => [
        'numeric' => ':attribute trebuie să fie mai mare sau egal cu :value.',
        'file'    => ':attribute trebuie să fie mai mare sau egal cu :value kilobytes.',
        'string'  => ':attribute trebuie să fie mai mare sau egal cu :value caractere.',
        'array'   => ':attribute trebuie să aibă cel puțin :value elemente.',
    ],

    'image'    => ':attribute trebuie să fie o imagine.',
    'in'       => ':attribute selectat este invalid.',
    'in_array' => ':attribute trebuie să existe în :other.',
    'integer'  => ':attribute trebuie să fie un număr întreg.',
    'ip'       => ':attribute trebuie să fie o adresă IP validă.',
    'ipv4'     => ':attribute trebuie să fie o adresă IPv4 validă.',
    'ipv6'     => ':attribute trebuie să fie o adresă IPv6 validă.',
    'json'     => ':attribute trebuie să fie un șir JSON valid.',

    'lt' => [
        'numeric' => ':attribute trebuie să fie mai mic decât :value.',
        'file'    => ':attribute trebuie să fie mai mic decât :value kilobytes.',
        'string'  => ':attribute trebuie să fie mai mic decât :value caractere.',
        'array'   => ':attribute trebuie să aibă mai puțin de :value elemente.',
    ],

    'lte' => [
        'numeric' => ':attribute trebuie să fie mai mic sau egal cu :value.',
        'file'    => ':attribute trebuie să fie mai mic sau egal cu :value kilobytes.',
        'string'  => ':attribute trebuie să fie mai mic sau egal cu :value caractere.',
        'array'   => ':attribute nu poate avea mai mult de :value elemente.',
    ],

    'max' => [
        'numeric' => ':attribute nu poate fi mai mare decât :max.',
        'file'    => ':attribute nu poate fi mai mare decât :max kilobytes.',
        'string'  => ':attribute nu poate fi mai mare decât :max caractere.',
        'array'   => ':attribute nu poate avea mai mult de :max elemente.',
    ],

    'mimes'     => ':attribute trebuie să fie un fișier de tipul: :values.',
    'mimetypes' => ':attribute trebuie să fie un fișier de tipul: :values.',

    'min' => [
        'numeric' => ':attribute trebuie să fie cel puțin :min.',
        'file'    => ':attribute trebuie să fie cel puțin :min kilobytes.',
        'string'  => ':attribute trebuie să fie cel puțin :min caractere.',
        'array'   => ':attribute trebuie să aibă cel puțin :min elemente.',
    ],

    'not_in'               => ':attribute selectat este invalid.',
    'not_regex'            => 'Formatul :attribute este invalid.',
    'numeric'              => ':attribute trebuie să fie un număr.',
    'present'              => 'Câmpul :attribute trebuie să fie prezent.',
    'regex'                => 'Formatul :attribute este invalid.',
    'required'             => 'Câmpul :attribute este obligatoriu.',
    'required_if'          => 'Câmpul :attribute este obligatoriu atunci când :other este :value.',
    'required_unless'      => 'Câmpul :attribute este obligatoriu, cu excepția cazului în care :other este :values.',
    'required_with'        => 'Câmpul :attribute este obligatoriu atunci când :values este prezent.',
    'required_with_all'    => 'Câmpul :attribute este obligatoriu atunci când :values sunt prezente.',
    'required_without'     => 'Câmpul :attribute este obligatoriu atunci când :values nu este prezent.',
    'required_without_all' => 'Câmpul :attribute este obligatoriu atunci când nici unul dintre :values nu este prezent.',
    'same'                 => ':attribute și :other trebuie să fie identice.',

    'size' => [
        'numeric' => ':attribute trebuie să fie :size.',
        'file'    => ':attribute trebuie să aibă :size kilobytes.',
        'string'  => ':attribute trebuie să aibă :size caractere.',
        'array'   => ':attribute trebuie să aibă :size elemente.',
    ],

    'string'   => ':attribute trebuie să fie un șir de caractere.',
    'timezone' => ':attribute trebuie să fie o zonă orară validă.',
    'unique'   => ':attribute a fost deja luat.',
    'uploaded' => ':attribute nu a fost încărcat.',
    'url'      => 'Formatul :attribute este invalid.',

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
            'rule-name' => 'mesaj personalizat',
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
