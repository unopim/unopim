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
    'accepted'        => 'At :attribute skal accepteres.',
    'active_url'      => ':attribute er ikke en gyldig URL.',
    'after'           => ':attribute skal være en dato efter :date.',
    'after_or_equal'  => ':attribute skal være en dato efter eller lig med :date.',
    'alpha'           => ':attribute må kun indeholde bogstaver.',
    'alpha_dash'      => ':attribute må kun indeholde bogstaver, tal, bindestreger og understregningstegn.',
    'alpha_num'       => ':attribute må kun indeholde bogstaver og tal.',
    'array'           => ':attribute skal være et array.',
    'before'          => ':attribute skal være en dato før :date.',
    'before_or_equal' => ':attribute skal være en dato før eller lig med :date.',

    'between' => [
        'numeric' => ':attribute skal være mellem :min og :max.',
        'file'    => ':attribute skal være mellem :min og :max kilobytes.',
        'string'  => ':attribute skal være mellem :min og :max tegn.',
        'array'   => ':attribute skal have mellem :min og :max elementer.',
    ],

    'boolean'        => ':attribute-feltet skal være sandt eller falsk.',
    'confirmed'      => ':attribute-bekræftelsen stemmer ikke overens.',
    'date'           => ':attribute er ikke en gyldig dato.',
    'date_format'    => ':attribute matcher ikke formatet :format.',
    'different'      => ':attribute og :other skal være forskellige.',
    'digits'         => ':attribute skal være :digits cifre.',
    'digits_between' => ':attribute skal være mellem :min og :max cifre.',
    'dimensions'     => ':attribute har ugyldige billeddimensioner.',
    'distinct'       => ':attribute-feltet har en duplikeret værdi.',
    'email'          => ':attribute skal være en gyldig e-mailadresse.',
    'exists'         => 'Den valgte :attribute er ugyldig.',
    'exists-value'   => ':input findes ikke.',
    'file'           => ':attribute skal være en fil.',
    'filled'         => ':attribute-feltet skal have en værdi.',

    'gt' => [
        'numeric' => ':attribute skal være større end :value.',
        'file'    => ':attribute skal være større end :value kilobytes.',
        'string'  => ':attribute skal være længere end :value tegn.',
        'array'   => ':attribute skal have flere end :value elementer.',
    ],

    'gte' => [
        'numeric' => ':attribute skal være større end eller lig med :value.',
        'file'    => ':attribute skal være større end eller lig med :value kilobytes.',
        'string'  => ':attribute skal være længere end eller lig med :value tegn.',
        'array'   => ':attribute skal have :value elementer eller flere.',
    ],

    'image'    => ':attribute skal være et billede.',
    'in'       => 'Den valgte :attribute er ugyldig.',
    'in_array' => ':attribute-feltet findes ikke i :other.',
    'integer'  => ':attribute skal være et heltal.',
    'ip'       => ':attribute skal være en gyldig IP-adresse.',
    'ipv4'     => ':attribute skal være en gyldig IPv4-adresse.',
    'ipv6'     => ':attribute skal være en gyldig IPv6-adresse.',
    'json'     => ':attribute skal være en gyldig JSON-streng.',

    'lt' => [
        'numeric' => ':attribute skal være mindre end :value.',
        'file'    => ':attribute skal være mindre end :value kilobytes.',
        'string'  => ':attribute skal være kortere end :value tegn.',
        'array'   => ':attribute skal have færre end :value elementer.',
    ],

    'lte' => [
        'numeric' => ':attribute skal være mindre end eller lig med :value.',
        'file'    => ':attribute skal være mindre end eller lig med :value kilobytes.',
        'string'  => ':attribute skal være kortere end eller lig med :value tegn.',
        'array'   => ':attribute må ikke have flere end :value elementer.',
    ],

    'max' => [
        'numeric' => ':attribute må ikke være større end :max.',
        'file'    => ':attribute må ikke være større end :max kilobytes.',
        'string'  => ':attribute må ikke være længere end :max tegn.',
        'array'   => ':attribute må ikke have flere end :max elementer.',
    ],

    'mimes'     => ':attribute skal være en fil af typen: :values.',
    'mimetypes' => ':attribute skal være en fil af typen: :values.',
    
    'min' => [
        'numeric' => ':attribute skal være mindst :min.',
        'file'    => ':attribute skal være mindst :min kilobytes.',
        'string'  => ':attribute skal være mindst :min tegn.',
        'array'   => ':attribute skal have mindst :min elementer.',
    ],

    'not_in'               => 'Den valgte :attribute er ugyldig.',
    'not_regex'            => ':attribute-formatet er ugyldigt.',
    'numeric'              => ':attribute skal være et tal.',
    'present'              => ':attribute-feltet skal være til stede.',
    'regex'                => ':attribute-formatet er ugyldigt.',
    'required'             => ':attribute-feltet er obligatorisk.',
    'required_if'          => ':attribute-feltet er obligatorisk, når :other er :value.',
    'required_unless'      => ':attribute-feltet er obligatorisk, medmindre :other er i :values.',
    'required_with'        => ':attribute-feltet er obligatorisk, når :values er til stede.',
    'required_with_all'    => ':attribute-feltet er obligatorisk, når :values er til stede.',
    'required_without'     => ':attribute-feltet er obligatorisk, når :values ikke er til stede.',
    'required_without_all' => ':attribute-feltet er obligatorisk, når ingen af :values er til stede.',
    'same'                 => ':attribute og :other skal matche.',

    'size' => [
        'numeric' => ':attribute skal være :size.',
        'file'    => ':attribute skal være :size kilobytes.',
        'string'  => ':attribute skal være :size tegn.',
        'array'   => ':attribute skal indeholde :size elementer.',
    ],

    'string'   => ':attribute skal være en streng.',
    'timezone' => ':attribute skal være en gyldig zone.',
    'unique'   => ':attribute er allerede taget.',
    'uploaded' => ':attribute kunne ikke uploades.',
    'url'      => ':attribute-formatet er ugyldigt.',

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
            'rule-name' => 'brugerdefineret besked',
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
