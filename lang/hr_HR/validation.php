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
    'accepted'        => ':attribute mora biti prihvaćen.',
    'active_url'      => ':attribute nije važeća URL adresa.',
    'after'           => ':attribute mora biti datum nakon :date.',
    'after_or_equal'  => ':attribute mora biti datum nakon ili jednak :date.',
    'alpha'           => ':attribute može sadržavati samo slova.',
    'alpha_dash'      => ':attribute može sadržavati samo slova, brojeve, crte i podcrte.',
    'alpha_num'       => ':attribute može sadržavati samo slova i brojeve.',
    'array'           => ':attribute mora biti niz.',
    'before'          => ':attribute mora biti datum prije :date.',
    'before_or_equal' => ':attribute mora biti datum prije ili jednak :date.',

    'between' => [
        'numeric' => ':attribute mora biti između :min i :max.',
        'file'    => ':attribute mora biti između :min i :max kilobajta.',
        'string'  => ':attribute mora biti između :min i :max znakova.',
        'array'   => ':attribute mora imati između :min i :max stavki.',
    ],

    'boolean'        => ':attribute polje mora biti istinito ili netočno.',
    'confirmed'      => 'Potvrda za :attribute se ne podudara.',
    'date'           => ':attribute nije važeći datum.',
    'date_format'    => ':attribute ne odgovara formatu :format.',
    'different'      => ':attribute i :other moraju biti različiti.',
    'digits'         => ':attribute mora imati :digits znamenki.',
    'digits_between' => ':attribute mora imati između :min i :max znamenki.',
    'dimensions'     => ':attribute ima nevažeće dimenzije slike.',
    'distinct'       => ':attribute polje ima dupliciranu vrijednost.',
    'email'          => ':attribute mora biti važeća e-mail adresa.',
    'exists'         => 'Odabrani :attribute je nevažeći.',
    'exists-value'   => ':input ne postoji.',
    'file'           => ':attribute mora biti datoteka.',
    'filled'         => ':attribute polje mora imati vrijednost.',

    'gt' => [
        'numeric' => ':attribute mora biti veće od :value.',
        'file'    => ':attribute mora biti veće od :value kilobajta.',
        'string'  => ':attribute mora imati više od :value znakova.',
        'array'   => ':attribute mora imati više od :value stavki.',
    ],

    'gte' => [
        'numeric' => ':attribute mora biti veće ili jednako :value.',
        'file'    => ':attribute mora biti veće ili jednako :value kilobajta.',
        'string'  => ':attribute mora biti veće ili jednako :value znakova.',
        'array'   => ':attribute mora imati barem :value stavki.',
    ],

    'image'    => ':attribute mora biti slika.',
    'in'       => 'Odabrani :attribute je nevažeći.',
    'in_array' => ':attribute polje ne postoji u :other.',
    'integer'  => ':attribute mora biti cijeli broj.',
    'ip'       => ':attribute mora biti važeća IP adresa.',
    'ipv4'     => ':attribute mora biti važeća IPv4 adresa.',
    'ipv6'     => ':attribute mora biti važeća IPv6 adresa.',
    'json'     => ':attribute mora biti važeći JSON niz.',

    'lt' => [
        'numeric' => ':attribute mora biti manji od :value.',
        'file'    => ':attribute mora biti manji od :value kilobajta.',
        'string'  => ':attribute mora biti kraći od :value znakova.',
        'array'   => ':attribute mora imati manje od :value stavki.',
    ],

    'lte' => [
        'numeric' => ':attribute mora biti manji ili jednak :value.',
        'file'    => ':attribute mora biti manji ili jednak :value kilobajta.',
        'string'  => ':attribute mora biti kraći ili jednak :value znakova.',
        'array'   => ':attribute ne smije imati više od :value stavki.',
    ],

    'max' => [
        'numeric' => ':attribute ne smije biti veći od :max.',
        'file'    => ':attribute ne smije biti veći od :max kilobajta.',
        'string'  => ':attribute ne smije biti duži od :max znakova.',
        'array'   => ':attribute ne smije imati više od :max stavki.',
    ],

    'mimes'     => ':attribute mora biti datoteka tipa: :values.',
    'mimetypes' => ':attribute mora biti datoteka tipa: :values.',
    
    'min' => [
        'numeric' => ':attribute mora biti najmanje :min.',
        'file'    => ':attribute mora biti najmanje :min kilobajta.',
        'string'  => ':attribute mora biti najmanje :min znakova.',
        'array'   => ':attribute mora imati najmanje :min stavki.',
    ],

    'not_in'               => 'Odabrani :attribute je nevažeći.',
    'not_regex'            => 'Format :attribute je nevažeći.',
    'numeric'              => ':attribute mora biti broj.',
    'present'              => ':attribute polje mora biti prisutno.',
    'regex'                => 'Format :attribute je nevažeći.',
    'required'             => ':attribute polje je obavezno.',
    'required_if'          => ':attribute polje je obavezno kada :other je :value.',
    'required_unless'      => ':attribute polje je obavezno osim ako :other nije :values.',
    'required_with'        => ':attribute polje je obavezno kada je :values prisutno.',
    'required_with_all'    => ':attribute polje je obavezno kada su :values prisutni.',
    'required_without'     => ':attribute polje je obavezno kada :values nije prisutno.',
    'required_without_all' => ':attribute polje je obavezno kada nijedan od :values nije prisutan.',
    'same'                 => ':attribute i :other se moraju podudarati.',

    'size' => [
        'numeric' => ':attribute mora biti :size.',
        'file'    => ':attribute mora biti :size kilobajta.',
        'string'  => ':attribute mora biti :size znakova.',
        'array'   => ':attribute mora imati :size stavki.',
    ],

    'string'   => ':attribute mora biti niz znakova.',
    'timezone' => ':attribute mora biti važeća vremenska zona.',
    'unique'   => ':attribute je već zauzet.',
    'uploaded' => ':attribute nije uspješno učitan.',
    'url'      => 'Format :attribute je nevažeći.',

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
            'rule-name' => 'prilagođena poruka',
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
