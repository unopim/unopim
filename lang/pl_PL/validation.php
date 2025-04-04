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
    'accepted'        => ':attribute musi być zaakceptowane.',
    'active_url'      => ':attribute nie jest poprawnym URL-em.',
    'after'           => ':attribute musi być datą późniejszą niż :date.',
    'after_or_equal'  => ':attribute musi być datą późniejszą lub równą :date.',
    'alpha'           => ':attribute może zawierać tylko litery.',
    'alpha_dash'      => ':attribute może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
    'alpha_num'       => ':attribute może zawierać tylko litery i cyfry.',
    'array'           => ':attribute musi być tablicą.',
    'before'          => ':attribute musi być datą wcześniejszą niż :date.',
    'before_or_equal' => ':attribute musi być datą wcześniejszą lub równą :date.',

    'between' => [
        'numeric' => ':attribute musi być pomiędzy :min a :max.',
        'file'    => ':attribute musi mieć rozmiar pomiędzy :min a :max kilobajtów.',
        'string'  => ':attribute musi mieć pomiędzy :min a :max znaków.',
        'array'   => ':attribute musi mieć pomiędzy :min a :max elementów.',
    ],

    'boolean'        => 'Pole :attribute musi być prawdą lub fałszem.',
    'confirmed'      => 'Potwierdzenie :attribute nie zgadza się.',
    'date'           => ':attribute nie jest poprawną datą.',
    'date_format'    => ':attribute nie pasuje do formatu :format.',
    'different'      => ':attribute i :other muszą być różne.',
    'digits'         => ':attribute musi mieć :digits cyfr.',
    'digits_between' => ':attribute musi mieć pomiędzy :min a :max cyfr.',
    'dimensions'     => ':attribute ma nieprawidłowe wymiary obrazu.',
    'distinct'       => 'Pole :attribute ma zduplikowaną wartość.',
    'email'          => ':attribute musi być poprawnym adresem e-mail.',
    'exists'         => 'Wybrany :attribute jest nieprawidłowy.',
    'exists-value'   => ':input nie istnieje.',
    'extensions'     => 'Pole :attribute musi mieć jedną z następujących rozszerzeń: :values.',
    'file'           => ':attribute musi być plikiem.',
    'filled'         => 'Pole :attribute musi mieć wartość.',

    'gt' => [
        'numeric' => ':attribute musi być większy niż :value.',
        'file'    => ':attribute musi mieć więcej niż :value kilobajtów.',
        'string'  => ':attribute musi mieć więcej niż :value znaków.',
        'array'   => ':attribute musi mieć więcej niż :value elementów.',
    ],

    'gte' => [
        'numeric' => ':attribute musi być większy lub równy :value.',
        'file'    => ':attribute musi mieć więcej niż lub równą :value kilobajtów.',
        'string'  => ':attribute musi mieć więcej niż lub równych :value znaków.',
        'array'   => ':attribute musi mieć co najmniej :value elementów.',
    ],

    'image'    => ':attribute musi być obrazem.',
    'in'       => 'Wybrany :attribute jest nieprawidłowy.',
    'in_array' => 'Pole :attribute nie istnieje w :other.',
    'integer'  => ':attribute musi być liczbą całkowitą.',
    'ip'       => ':attribute musi być poprawnym adresem IP.',
    'ipv4'     => ':attribute musi być poprawnym adresem IPv4.',
    'ipv6'     => ':attribute musi być poprawnym adresem IPv6.',
    'json'     => ':attribute musi być poprawnym ciągiem JSON.',

    'lt' => [
        'numeric' => ':attribute musi być mniejszy niż :value.',
        'file'    => ':attribute musi mieć mniej niż :value kilobajtów.',
        'string'  => ':attribute musi mieć mniej niż :value znaków.',
        'array'   => ':attribute musi mieć mniej niż :value elementów.',
    ],

    'lte' => [
        'numeric' => ':attribute musi być mniejszy lub równy :value.',
        'file'    => ':attribute musi mieć mniej niż lub równy :value kilobajtów.',
        'string'  => ':attribute musi mieć mniej niż lub równych :value znaków.',
        'array'   => ':attribute musi mieć maksymalnie :value elementów.',
    ],

    'max' => [
        'numeric' => ':attribute nie może być większy niż :max.',
        'file'    => ':attribute nie może mieć więcej niż :max kilobajtów.',
        'string'  => ':attribute nie może mieć więcej niż :max znaków.',
        'array'   => ':attribute nie może mieć więcej niż :max elementów.',
    ],

    'mimes'     => ':attribute musi być plikiem typu: :values.',
    'mimetypes' => ':attribute musi być plikiem typu: :values.',

    'min' => [
        'numeric' => ':attribute musi być co najmniej :min.',
        'file'    => ':attribute musi mieć co najmniej :min kilobajtów.',
        'string'  => ':attribute musi mieć co najmniej :min znaków.',
        'array'   => ':attribute musi mieć co najmniej :min elementów.',
    ],

    'not_in'               => 'Wybrany :attribute jest nieprawidłowy.',
    'not_regex'            => 'Format :attribute jest nieprawidłowy.',
    'numeric'              => ':attribute musi być liczbą.',
    'present'              => 'Pole :attribute musi istnieć.',
    'regex'                => 'Format :attribute jest nieprawidłowy.',
    'required'             => 'Pole :attribute jest wymagane.',
    'required_if'          => 'Pole :attribute jest wymagane, gdy :other jest :value.',
    'required_unless'      => 'Pole :attribute jest wymagane, chyba że :other jest w :values.',
    'required_with'        => 'Pole :attribute jest wymagane, gdy :values jest obecne.',
    'required_with_all'    => 'Pole :attribute jest wymagane, gdy :values są obecne.',
    'required_without'     => 'Pole :attribute jest wymagane, gdy :values nie jest obecne.',
    'required_without_all' => 'Pole :attribute jest wymagane, gdy żadne z :values nie jest obecne.',
    'same'                 => ':attribute i :other muszą być identyczne.',

    'size' => [
        'numeric' => ':attribute musi mieć :size.',
        'file'    => ':attribute musi mieć :size kilobajtów.',
        'string'  => ':attribute musi mieć :size znaków.',
        'array'   => ':attribute musi mieć :size elementów.',
    ],

    'string'   => ':attribute musi być ciągiem znaków.',
    'timezone' => ':attribute musi być poprawną strefą czasową.',
    'unique'   => ':attribute jest już zajęte.',
    'uploaded' => ':attribute nie zostało przesłane.',
    'url'      => 'Format :attribute jest nieprawidłowy.',

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
            'rule-name' => 'niestandardowa wiadomość',
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
