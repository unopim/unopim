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
    'accepted'        => ':attribute måste accepteras.',
    'active_url'      => ':attribute är inte en giltig URL.',
    'after'           => ':attribute måste vara ett datum efter :date.',
    'after_or_equal'  => ':attribute måste vara ett datum som är lika med eller efter :date.',
    'alpha'           => ':attribute får endast innehålla bokstäver.',
    'alpha_dash'      => ':attribute får endast innehålla bokstäver, siffror, bindestreck och understreck.',
    'alpha_num'       => ':attribute får endast innehålla bokstäver och siffror.',
    'array'           => ':attribute måste vara en array.',
    'before'          => ':attribute måste vara ett datum innan :date.',
    'before_or_equal' => ':attribute måste vara ett datum lika med eller innan :date.',

    'between' => [
        'numeric' => ':attribute måste vara mellan :min och :max.',
        'file'    => ':attribute måste vara mellan :min och :max kilobyte.',
        'string'  => ':attribute måste vara mellan :min och :max tecken.',
        'array'   => ':attribute måste ha mellan :min och :max element.',
    ],

    'boolean'        => ':attribute måste vara sant eller falskt.',
    'confirmed'      => 'Bekräftelsen av :attribute stämmer inte.',
    'date'           => ':attribute är inte ett giltigt datum.',
    'date_format'    => ':attribute matchar inte formatet :format.',
    'different'      => ':attribute och :other måste vara olika.',
    'digits'         => ':attribute måste vara :digits siffror.',
    'digits_between' => ':attribute måste vara mellan :min och :max siffror.',
    'dimensions'     => 'Bildens dimensioner för :attribute är ogiltiga.',
    'distinct'       => ':attribute har ett duplicerat värde.',
    'email'          => ':attribute måste vara en giltig e-postadress.',
    'exists'         => ':attribute som valts är ogiltigt.',
    'extensions'     => 'Fältet :attribute måste ha en av följande filändelser: :values.',
    'file'           => ':attribute måste vara en fil.',
    'filled'         => ':attribute måste ha ett värde.',

    'gt' => [
        'numeric' => ':attribute måste vara större än :value.',
        'file'    => ':attribute måste vara större än :value kilobyte.',
        'string'  => ':attribute måste vara större än :value tecken.',
        'array'   => ':attribute måste ha fler än :value element.',
    ],

    'gte' => [
        'numeric' => ':attribute måste vara större än eller lika med :value.',
        'file'    => ':attribute måste vara större än eller lika med :value kilobyte.',
        'string'  => ':attribute måste vara större än eller lika med :value tecken.',
        'array'   => ':attribute måste ha minst :value element.',
    ],

    'image'    => ':attribute måste vara en bild.',
    'in'       => ':attribute som valts är ogiltigt.',
    'in_array' => ':attribute måste finnas i :other.',
    'integer'  => ':attribute måste vara ett heltal.',
    'ip'       => ':attribute måste vara en giltig IP-adress.',
    'ipv4'     => ':attribute måste vara en giltig IPv4-adress.',
    'ipv6'     => ':attribute måste vara en giltig IPv6-adress.',
    'json'     => ':attribute måste vara en giltig JSON-sträng.',

    'lt' => [
        'numeric' => ':attribute måste vara mindre än :value.',
        'file'    => ':attribute måste vara mindre än :value kilobyte.',
        'string'  => ':attribute måste vara mindre än :value tecken.',
        'array'   => ':attribute måste ha färre än :value element.',
    ],

    'lte' => [
        'numeric' => ':attribute måste vara mindre än eller lika med :value.',
        'file'    => ':attribute måste vara mindre än eller lika med :value kilobyte.',
        'string'  => ':attribute måste vara mindre än eller lika med :value tecken.',
        'array'   => ':attribute kan inte ha fler än :value element.',
    ],

    'max' => [
        'numeric' => ':attribute kan inte vara större än :max.',
        'file'    => ':attribute kan inte vara större än :max kilobyte.',
        'string'  => ':attribute kan inte vara större än :max tecken.',
        'array'   => ':attribute kan inte ha fler än :max element.',
    ],

    'mimes'     => ':attribute måste vara en fil av typen: :values.',
    'mimetypes' => ':attribute måste vara en fil av typen: :values.',

    'min' => [
        'numeric' => ':attribute måste vara minst :min.',
        'file'    => ':attribute måste vara minst :min kilobyte.',
        'string'  => ':attribute måste vara minst :min tecken.',
        'array'   => ':attribute måste ha minst :min element.',
    ],

    'not_in'               => ':attribute som valts är ogiltigt.',
    'not_regex'            => 'Formatet på :attribute är ogiltigt.',
    'numeric'              => ':attribute måste vara ett nummer.',
    'present'              => ':attribute-fältet måste vara närvarande.',
    'regex'                => 'Formatet på :attribute är ogiltigt.',
    'required'             => ':attribute-fältet är obligatoriskt.',
    'required_if'          => ':attribute-fältet är obligatoriskt när :other är :value.',
    'required_unless'      => ':attribute-fältet är obligatoriskt om inte :other är :values.',
    'required_with'        => ':attribute-fältet är obligatoriskt när :values är närvarande.',
    'required_with_all'    => ':attribute-fältet är obligatoriskt när :values är närvarande.',
    'required_without'     => ':attribute-fältet är obligatoriskt när :values inte är närvarande.',
    'required_without_all' => ':attribute-fältet är obligatoriskt när ingen av :values är närvarande.',
    'same'                 => ':attribute och :other måste vara lika.',

    'size' => [
        'numeric' => ':attribute måste vara :size.',
        'file'    => ':attribute måste vara :size kilobyte.',
        'string'  => ':attribute måste vara :size tecken.',
        'array'   => ':attribute måste ha :size element.',
    ],

    'string'   => ':attribute måste vara en sträng.',
    'timezone' => ':attribute måste vara en giltig tidszon.',
    'unique'   => ':attribute är redan taget.',
    'uploaded' => ':attribute misslyckades med att laddas upp.',
    'url'      => 'Formatet på :attribute är ogiltigt.',

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
            'rule-name' => 'anpassat meddelande',
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
