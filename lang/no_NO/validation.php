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
    'accepted'        => ':attribute må aksepteres.',
    'active_url'      => ':attribute er ikke en gyldig URL.',
    'after'           => ':attribute må være en dato etter :date.',
    'after_or_equal'  => ':attribute må være en dato etter eller lik :date.',
    'alpha'           => ':attribute kan bare inneholde bokstaver.',
    'alpha_dash'      => ':attribute kan bare inneholde bokstaver, tall, bindestreker og understrekingstegn.',
    'alpha_num'       => ':attribute kan bare inneholde bokstaver og tall.',
    'array'           => ':attribute må være en matrise.',
    'before'          => ':attribute må være en dato før :date.',
    'before_or_equal' => ':attribute må være en dato før eller lik :date.',

    'between' => [
        'numeric' => ':attribute må være mellom :min og :max.',
        'file'    => ':attribute må være mellom :min og :max kilobyte.',
        'string'  => ':attribute må være mellom :min og :max tegn.',
        'array'   => ':attribute må ha mellom :min og :max elementer.',
    ],

    'boolean'        => ':attribute-feltet må være sant eller falskt.',
    'confirmed'      => ':attribute bekreftelsen stemmer ikke.',
    'date'           => ':attribute er ikke en gyldig dato.',
    'date_format'    => ':attribute samsvarer ikke med formatet :format.',
    'different'      => ':attribute og :other må være forskjellige.',
    'digits'         => ':attribute må være :digits sifre.',
    'digits_between' => ':attribute må være mellom :min og :max sifre.',
    'dimensions'     => ':attribute har ugyldige bilde dimensjoner.',
    'distinct'       => ':attribute-feltet har en duplikatverdi.',
    'email'          => ':attribute må være en gyldig e-postadresse.',
    'exists'         => 'Den valgte :attribute er ugyldig.',
    'exists-value'   => ':input finnes ikke.',
    'file'           => ':attribute må være en fil.',
    'filled'         => ':attribute-feltet må ha en verdi.',

    'gt' => [
        'numeric' => ':attribute må være større enn :value.',
        'file'    => ':attribute må være større enn :value kilobyte.',
        'string'  => ':attribute må være lengre enn :value tegn.',
        'array'   => ':attribute må ha mer enn :value elementer.',
    ],

    'gte' => [
        'numeric' => ':attribute må være større enn eller lik :value.',
        'file'    => ':attribute må være større enn eller lik :value kilobyte.',
        'string'  => ':attribute må være lengre enn eller lik :value tegn.',
        'array'   => ':attribute må ha minst :value elementer.',
    ],

    'image'    => ':attribute må være et bilde.',
    'in'       => 'Den valgte :attribute er ugyldig.',
    'in_array' => ':attribute-feltet finnes ikke i :other.',
    'integer'  => ':attribute må være et helt tall.',
    'ip'       => ':attribute må være en gyldig IP-adresse.',
    'ipv4'     => ':attribute må være en gyldig IPv4-adresse.',
    'ipv6'     => ':attribute må være en gyldig IPv6-adresse.',
    'json'     => ':attribute må være en gyldig JSON-streng.',

    'lt' => [
        'numeric' => ':attribute må være mindre enn :value.',
        'file'    => ':attribute må være mindre enn :value kilobyte.',
        'string'  => ':attribute må være kortere enn :value tegn.',
        'array'   => ':attribute må ha færre enn :value elementer.',
    ],

    'lte' => [
        'numeric' => ':attribute må være mindre enn eller lik :value.',
        'file'    => ':attribute må være mindre enn eller lik :value kilobyte.',
        'string'  => ':attribute må være kortere enn eller lik :value tegn.',
        'array'   => ':attribute må ikke ha mer enn :value elementer.',
    ],

    'max' => [
        'numeric' => ':attribute kan ikke være større enn :max.',
        'file'    => ':attribute kan ikke være større enn :max kilobyte.',
        'string'  => ':attribute kan ikke være lengre enn :max tegn.',
        'array'   => ':attribute kan ikke ha mer enn :max elementer.',
    ],

    'mimes'     => ':attribute må være en filtype av: :values.',
    'mimetypes' => ':attribute må være en filtype av: :values.',
    
    'min' => [
        'numeric' => ':attribute må være minst :min.',
        'file'    => ':attribute må være minst :min kilobyte.',
        'string'  => ':attribute må være minst :min tegn.',
        'array'   => ':attribute må ha minst :min elementer.',
    ],

    'not_in'               => 'Den valgte :attribute er ugyldig.',
    'not_regex'            => 'Formatet på :attribute er ugyldig.',
    'numeric'              => ':attribute må være et tall.',
    'present'              => ':attribute-feltet må være til stede.',
    'regex'                => 'Formatet på :attribute er ugyldig.',
    'required'             => ':attribute-feltet er påkrevd.',
    'required_if'          => ':attribute-feltet er påkrevd når :other er :value.',
    'required_unless'      => ':attribute-feltet er påkrevd med mindre :other er :values.',
    'required_with'        => ':attribute-feltet er påkrevd når :values er til stede.',
    'required_with_all'    => ':attribute-feltet er påkrevd når :values er til stede.',
    'required_without'     => ':attribute-feltet er påkrevd når :values ikke er til stede.',
    'required_without_all' => ':attribute-feltet er påkrevd når ingen av :values er til stede.',
    'same'                 => ':attribute og :other må være like.',

    'size' => [
        'numeric' => ':attribute må være :size.',
        'file'    => ':attribute må være :size kilobyte.',
        'string'  => ':attribute må være :size tegn.',
        'array'   => ':attribute må ha :size elementer.',
    ],

    'string'   => ':attribute må være en streng.',
    'timezone' => ':attribute må være et gyldig tidssone.',
    'unique'   => ':attribute er allerede tatt.',
    'uploaded' => ':attribute ble ikke lastet opp.',
    'url'      => 'Formatet på :attribute er ugyldig.',

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
            'rule-name' => 'egendefinert melding',
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
