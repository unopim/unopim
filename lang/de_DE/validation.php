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
    'accepted'        => 'Das :attribute muss akzeptiert werden.',
    'active_url'      => 'Das :attribute ist keine gültige URL.',
    'after'           => 'Das :attribute muss ein Datum nach :date sein.',
    'after_or_equal'  => 'Das :attribute muss ein Datum nach oder gleich :date sein.',
    'alpha'           => 'Das :attribute darf nur Buchstaben enthalten.',
    'alpha_dash'      => 'Das :attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num'       => 'Das :attribute darf nur Buchstaben und Zahlen enthalten.',
    'array'           => 'Das :attribute muss ein Array sein.',
    'before'          => 'Das :attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute muss ein Datum vor oder gleich :date sein.',

    'between' => [
        'numeric' => 'Das :attribute muss zwischen :min und :max liegen.',
        'file'    => 'Das :attribute muss zwischen :min und :max Kilobyte liegen.',
        'string'  => 'Das :attribute muss zwischen :min und :max Zeichen liegen.',
        'array'   => 'Das :attribute muss zwischen :min und :max Elemente haben.',
    ],

    'boolean'        => 'Das :attribute-Feld muss wahr oder falsch sein.',
    'confirmed'      => 'Die :attribute-Bestätigung stimmt nicht überein.',
    'date'           => 'Das :attribute ist kein gültiges Datum.',
    'date_format'    => 'Das :attribute stimmt nicht mit dem Format :format überein.',
    'different'      => 'Das :attribute und :other müssen unterschiedlich sein.',
    'digits'         => 'Das :attribute muss :digits Ziffern sein.',
    'digits_between' => 'Das :attribute muss zwischen :min und :max Ziffern liegen.',
    'dimensions'     => 'Das :attribute hat ungültige Bildabmessungen.',
    'distinct'       => 'Das :attribute-Feld hat einen doppelten Wert.',
    'email'          => 'Das :attribute muss eine gültige E-Mail-Adresse sein.',
    'exists'         => 'Das ausgewählte :attribute ist ungültig.',
    'exists-value'   => 'Die :input existiert nicht.',
    'file'           => 'Das :attribute muss eine Datei sein.',
    'filled'         => 'Das Feld :attribute muss einen Wert haben.',

    'gt' => [
        'numeric' => 'Das :attribute muss größer als :value sein.',
        'file'    => 'Das :attribute muss größer als :value Kilobyte sein.',
        'string'  => 'Das :attribute muss größer als :value Zeichen sein.',
        'array'   => 'Das :attribute muss mehr als :value Elemente haben.',
    ],

    'gte' => [
        'numeric' => 'Das :attribute muss größer oder gleich :value sein.',
        'file'    => 'Das :attribute muss größer oder gleich :value Kilobyte sein.',
        'string'  => 'Das :attribute muss größer oder gleich :value Zeichen sein.',
        'array'   => 'Das :attribute muss :value Elemente oder mehr haben.',
    ],

    'image'    => 'Das :attribute muss ein Bild sein.',
    'in'       => 'Das ausgewählte :attribute ist ungültig.',
    'in_array' => 'Das :attribute-Feld existiert nicht in :other.',
    'integer'  => 'Das :attribute muss eine Ganzzahl sein.',
    'ip'       => 'Das :attribute muss eine gültige IP-Adresse sein.',
    'ipv4'     => 'Das :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6'     => 'Das :attribute muss eine gültige IPv6-Adresse sein.',
    'json'     => 'Das :attribute muss eine gültige JSON-Zeichenfolge sein.',

    'lt' => [
        'numeric' => 'Das :attribute muss kleiner als :value sein.',
        'file'    => 'Das :attribute muss kleiner als :value Kilobyte sein.',
        'string'  => 'Das :attribute muss kleiner als :value Zeichen sein.',
        'array'   => 'Das :attribute muss weniger als :value Elemente haben.',
    ],

    'lte' => [
        'numeric' => 'Das :attribute muss kleiner oder gleich :value sein.',
        'file'    => 'Das :attribute muss kleiner oder gleich :value Kilobyte sein.',
        'string'  => 'Das :attribute muss kleiner oder gleich :value Zeichen sein.',
        'array'   => 'Das :attribute darf nicht mehr als :value Elemente haben.',
    ],

    'max' => [
        'numeric' => 'Das :attribute darf nicht größer als :max sein.',
        'file'    => 'Das :attribute darf nicht größer als :max Kilobyte sein.',
        'string'  => 'Das :attribute darf nicht größer als :max Zeichen sein.',
        'array'   => 'Das :attribute darf nicht mehr als :max Elemente haben.',
    ],

    'mimes'     => 'Das :attribute muss eine Datei vom Typ: :values ​​sein.',
    'mimetypes' => 'Das :attribute muss eine Datei vom Typ: :values ​​sein.',

    'min' => [
        'numeric' => 'Das :attribute muss mindestens :min sein.',
        'file'    => 'Das :attribute muss mindestens :min Kilobyte sein.',
        'string'  => 'Das :attribute muss mindestens :min Zeichen haben.',
        'array'   => 'Das :attribute muss mindestens :min Elemente haben.',
    ],

    'not_in'               => 'Das ausgewählte :attribute ist ungültig.',
    'not_regex'            => 'Das :attribute-Format ist ungültig.',
    'numeric'              => 'Das :attribute muss eine Zahl sein.',
    'present'              => 'Das :attribute-Feld muss vorhanden sein.',
    'regex'                => 'Das :attribute-Format ist ungültig.',
    'required'             => 'Das :attribute-Feld ist erforderlich.',
    'required_if'          => 'Das :attribute-Feld ist erforderlich, wenn :other :value ist.',
    'required_unless'      => 'Das Feld :attribute ist erforderlich, sofern :other nicht in :values ​​enthalten ist.',
    'required_with'        => 'Das Feld :attribute ist erforderlich, wenn :values ​​vorhanden ist.',
    'required_with_all'    => 'Das Feld :attribute ist erforderlich, wenn :values ​​vorhanden ist.',
    'required_without'     => 'Das Feld :attribute ist erforderlich, wenn :values ​​nicht vorhanden ist.',
    'required_without_all' => 'Das Feld :attribute ist erforderlich, wenn keines von :values ​​vorhanden ist.',
    'same'                 => ':attribute und :other müssen übereinstimmen.',

    'size' => [
        'numeric' => 'Das :attribute muss :size sein.',
        'file'    => 'Das :attribute muss :size Kilobyte sein.',
        'string'  => 'Das :attribute muss :size Zeichen sein.',
        'array'   => 'Das :attribute muss :size Elemente enthalten.',
    ],

    'string'   => 'Das :attribute muss eine Zeichenfolge sein.',
    'timezone' => 'Das :attribute muss eine gültige Zone sein.',
    'unique'   => 'Das :attribute ist bereits vergeben.',
    'uploaded' => 'Das :attribute konnte nicht hochgeladen werden.',
    'url'      => 'Das :attribute ist ungültig.',

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
            'rule-name' => 'benutzerdefinierte Nachricht',
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
