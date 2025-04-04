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
    'accepted'        => 'Het kenmerk :attribute moet geaccepteerd worden.',
    'active_url'      => 'Het kenmerk :attribute is geen geldige URL.',
    'after'           => 'Het kenmerk :attribute moet een datum na :date zijn.',
    'after_or_equal'  => 'Het kenmerk :attribute moet een datum na of gelijk aan :date zijn.',
    'alpha'           => 'Het kenmerk :attribute mag alleen letters bevatten.',
    'alpha_dash'      => 'Het kenmerk :attribute mag alleen letters, cijfers, streepjes en underscores bevatten.',
    'alpha_num'       => 'Het kenmerk :attribute mag alleen letters en cijfers bevatten.',
    'array'           => 'Het kenmerk :attribute moet een array zijn.',
    'before'          => 'Het kenmerk :attribute moet een datum vóór :date zijn.',
    'before_or_equal' => 'Het kenmerk :attribute moet een datum vóór of gelijk aan :date zijn.',

    'between' => [
        'numeric' => 'Het :attribute moet tussen :min en :max liggen.',
        'file'    => 'Het :attribute moet tussen :min en :max kilobytes liggen.',
        'string'  => 'Het :attribute moet tussen :min en :max tekens liggen.',
        'array'   => 'Het :attribute moet tussen :min en :max items hebben.',
    ],

    'boolean'        => 'Het veld :attribute moet true of false zijn.',
    'confirmed'      => 'De bevestiging van :attribute komt niet overeen.',
    'date'           => 'Het :attribute is geen geldige datum.',
    'date_format'    => 'Het :attribute komt niet overeen met het formaat :format.',
    'different'      => 'Het :attribute en :other moeten verschillend zijn.',
    'digits'         => 'Het :attribute moet :digits cijfers zijn.',
    'digits_between' => 'Het :attribute moet tussen :min en :max cijfers zijn.',
    'dimensions'     => 'Het :attribute heeft ongeldige afbeeldingsafmetingen.',
    'distinct'       => 'Het veld :attribute heeft een dubbele waarde.',
    'email'          => 'Het :attribute moet een geldig e-mailadres zijn.',
    'exists'         => 'Het geselecteerde :attribute is ongeldig.',
    'exists-value'   => 'De :input bestaat niet.',
    'extensions'     => 'Het :attribute veld moet een van de volgende extensies hebben: :values.',
    'file'           => 'Het :attribute moet een bestand zijn.',
    'filled'         => 'Het veld :attribute moet een waarde hebben.',

    'gt' => [
        'numeric' => 'Het :attribute moet groter zijn dan :value.',
        'file'    => 'Het :attribute moet groter zijn dan :value kilobytes.',
        'string'  => 'Het :attribute moet groter zijn dan :value tekens.',
        'array'   => 'Het :attribute moet meer dan :value items hebben.',
    ],

    'gte' => [
        'numeric' => 'Het :attribute moet groter zijn dan of gelijk zijn aan :value.',
        'file'    => 'Het :attribute moet groter zijn dan of gelijk zijn aan :value kilobytes.',
        'string'  => 'Het :attribute moet groter zijn dan of gelijk zijn aan :value tekens.',
        'array'   => 'Het :attribute moet :value items of meer hebben.',
    ],

    'image'    => 'Het :attribute moet een afbeelding zijn.',
    'in'       => 'Het geselecteerde :attribute is ongeldig.',
    'in_array' => 'Het veld :attribute bestaat niet in :other.',
    'integer'  => 'Het :attribute moet een geheel getal zijn.',
    'ip'       => 'Het :attribute moet een geldig IP-adres zijn.',
    'ipv4'     => 'Het :attribute moet een geldig IPv4-adres zijn.',
    'ipv6'     => 'Het :attribute moet een geldig IPv6-adres zijn.',
    'json'     => 'Het :attribute moet een geldige JSON-tekenreeks zijn.',

    'lt' => [
        'numeric' => 'Het :attribute moet kleiner zijn dan :value.',
        'file'    => 'Het :attribute moet kleiner zijn dan :value kilobytes.',
        'string'  => 'Het :attribute moet kleiner zijn dan :value tekens.',
        'array'   => 'Het :attribute moet minder dan :value items hebben.',
    ],

    'lte' => [
        'numeric' => 'Het :attribute moet kleiner zijn dan of gelijk aan :value.',
        'file'    => 'Het :attribute moet kleiner zijn dan of gelijk aan :value kilobytes.',
        'string'  => 'Het :attribute moet kleiner zijn dan of gelijk aan :value tekens.',
        'array'   => 'Het :attribute mag niet meer dan :value items hebben.',
    ],

    'max' => [
        'numeric' => 'Het :attribute mag niet groter zijn dan :max.',
        'file'    => 'Het :attribute mag niet groter zijn dan :max kilobytes.',
        'string'  => 'Het :attribute mag niet groter zijn dan :max tekens.',
        'array'   => 'Het :attribute mag niet meer dan :max items hebben.',
    ],

    'mimes'     => 'Het :attribute moet een bestand zijn van het type: :values.',
    'mimetypes' => 'Het :attribute moet een bestand zijn van het type: :values.',

    'min' => [
        'numeric' => 'Het :attribute moet minstens :min zijn.',
        'file'    => 'Het :attribute moet minstens :min kilobytes zijn.',
        'string'  => 'Het :attribute moet minstens :min tekens zijn.',
        'array'   => 'Het :attribute moet minstens :min items hebben.',
    ],

    'not_in'               => 'De geselecteerde :attribute is ongeldig.',
    'not_regex'            => 'De :attribute indeling is ongeldig.',
    'numeric'              => 'De :attribute moet een getal zijn.',
    'present'              => 'Het :attribute veld moet aanwezig zijn.',
    'regex'                => 'De :attribute indeling is ongeldig.',
    'required'             => 'Het :attribute veld is verplicht.',
    'required_if'          => 'Het :attribute veld is verplicht wanneer :other :value is.',
    'required_unless'      => 'Het :attribute veld is verplicht tenzij :other in :values ​​staat.',
    'required_with'        => 'Het :attribute veld is verplicht wanneer :values ​​aanwezig is.',
    'required_with_all'    => 'Het :attribute veld is verplicht wanneer :values ​​aanwezig is.',
    'required_without'     => 'Het :attribute veld is verplicht wanneer :values ​​niet aanwezig is.',
    'required_without_all' => 'Het :attribute veld is verplicht wanneer geen van de :values ​​aanwezig is.',
    'same'                 => 'De :attribute en :other moeten overeenkomen.',

    'size' => [
        'numeric' => 'Het :attribute moet :size zijn.',
        'file'    => 'Het :attribute moet :size kilobytes zijn.',
        'string'  => 'Het :attribute moet :size tekens zijn.',
        'array'   => 'Het :attribute moet :size items bevatten.',
    ],

    'string'   => 'Het kenmerk :attribute moet een string zijn.',
    'timezone' => 'Het kenmerk :attribute moet een geldige zone zijn.',
    'unique'   => 'Het kenmerk :attribute is al in gebruik.',
    'uploaded' => 'Het kenmerk :attribute kon niet worden geüpload.',
    'url'      => 'Het kenmerk :attribute formaat is ongeldig.',

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
            'rule-name' => 'aangepast bericht',
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
