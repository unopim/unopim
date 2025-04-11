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
    'accepted'        => ':attribute on hyväksyttävä.',
    'active_url'      => ':attribute ei ole voimassa oleva URL-osoite.',
    'after'           => ':attribute on oltava päiväys :date:n jälkeen.',
    'after_or_equal'  => ':attribute on oltava päiväys, joka on sama tai myöhemmin kuin :date.',
    'alpha'           => ':attribute voi sisältää vain kirjaimia.',
    'alpha_dash'      => ':attribute voi sisältää vain kirjaimia, numeroita, viivoja ja alaviivoja.',
    'alpha_num'       => ':attribute voi sisältää vain kirjaimia ja numeroita.',
    'array'           => ':attribute on oltava taulukko.',
    'before'          => ':attribute on oltava päiväys ennen :date.',
    'before_or_equal' => ':attribute on oltava päiväys, joka on sama tai aikaisempi kuin :date.',

    'between' => [
        'numeric' => ':attribute on oltava välillä :min ja :max.',
        'file'    => ':attribute on oltava välillä :min ja :max kilotavua.',
        'string'  => ':attribute on oltava välillä :min ja :max merkkiä.',
        'array'   => ':attribute on oltava välillä :min ja :max kohdetta.',
    ],

    'boolean'        => ':attribute-kentän täytyy olla tosi tai epätosi.',
    'confirmed'      => ':attribute-vahvistus ei täsmää.',
    'date'           => ':attribute ei ole kelvollinen päivämäärä.',
    'date_format'    => ':attribute ei täsmää muotoon :format.',
    'different'      => ':attribute ja :other täytyy olla eri.',
    'digits'         => ':attribute täytyy olla :digits numeroa.',
    'digits_between' => ':attribute täytyy olla välillä :min ja :max numeroa.',
    'dimensions'     => ':attribute on virheelliset kuvan mitat.',
    'distinct'       => ':attribute-kenttä sisältää kaksoisarvon.',
    'email'          => ':attribute täytyy olla kelvollinen sähköpostiosoite.',
    'exists'         => 'Valittu :attribute on virheellinen.',
    'exists-value'   => ':input ei ole olemassa.',
    'extensions'     => ':attribute-kentässä on oltava yksi seuraavista laajennuksista: :values.',
    'file'           => ':attribute täytyy olla tiedosto.',
    'filled'         => ':attribute-kenttä täytyy sisältää arvo.',

    'gt' => [
        'numeric' => ':attribute täytyy olla suurempi kuin :value.',
        'file'    => ':attribute täytyy olla suurempi kuin :value kilotavua.',
        'string'  => ':attribute täytyy olla pidempi kuin :value merkkiä.',
        'array'   => ':attribute täytyy sisältää enemmän kuin :value kohdetta.',
    ],

    'gte' => [
        'numeric' => ':attribute täytyy olla suurempi tai yhtä suuri kuin :value.',
        'file'    => ':attribute täytyy olla suurempi tai yhtä suuri kuin :value kilotavua.',
        'string'  => ':attribute täytyy olla pidempi tai yhtä pitkä kuin :value merkkiä.',
        'array'   => ':attribute täytyy sisältää vähintään :value kohdetta.',
    ],

    'image'    => ':attribute täytyy olla kuva.',
    'in'       => 'Valittu :attribute on virheellinen.',
    'in_array' => ':attribute-kenttä ei ole :other:n sisällä.',
    'integer'  => ':attribute täytyy olla kokonaisluku.',
    'ip'       => ':attribute täytyy olla kelvollinen IP-osoite.',
    'ipv4'     => ':attribute täytyy olla kelvollinen IPv4-osoite.',
    'ipv6'     => ':attribute täytyy olla kelvollinen IPv6-osoite.',
    'json'     => ':attribute täytyy olla kelvollinen JSON-merkkijono.',

    'lt' => [
        'numeric' => ':attribute täytyy olla pienempi kuin :value.',
        'file'    => ':attribute täytyy olla pienempi kuin :value kilotavua.',
        'string'  => ':attribute täytyy olla lyhyempi kuin :value merkkiä.',
        'array'   => ':attribute täytyy sisältää vähemmän kuin :value kohdetta.',
    ],

    'lte' => [
        'numeric' => ':attribute täytyy olla pienempi tai yhtä suuri kuin :value.',
        'file'    => ':attribute täytyy olla pienempi tai yhtä suuri kuin :value kilotavua.',
        'string'  => ':attribute täytyy olla lyhyempi tai yhtä pitkä kuin :value merkkiä.',
        'array'   => ':attribute täytyy sisältää enintään :value kohdetta.',
    ],

    'max' => [
        'numeric' => ':attribute ei saa olla suurempi kuin :max.',
        'file'    => ':attribute ei saa olla suurempi kuin :max kilotavua.',
        'string'  => ':attribute ei saa olla pidempi kuin :max merkkiä.',
        'array'   => ':attribute ei saa sisältää enempää kuin :max kohdetta.',
    ],

    'mimes'     => ':attribute täytyy olla tiedosto, jonka tyyppi on: :values.',
    'mimetypes' => ':attribute täytyy olla tiedosto, jonka tyyppi on: :values.',

    'min' => [
        'numeric' => ':attribute täytyy olla vähintään :min.',
        'file'    => ':attribute täytyy olla vähintään :min kilotavua.',
        'string'  => ':attribute täytyy olla vähintään :min merkkiä.',
        'array'   => ':attribute täytyy sisältää vähintään :min kohdetta.',
    ],

    'not_in'               => 'Valittu :attribute on virheellinen.',
    'not_regex'            => ':attribute-muoto on virheellinen.',
    'numeric'              => ':attribute täytyy olla numero.',
    'present'              => ':attribute-kenttä täytyy olla läsnä.',
    'regex'                => ':attribute-muoto on virheellinen.',
    'required'             => ':attribute-kenttä on pakollinen.',
    'required_if'          => ':attribute-kenttä on pakollinen, kun :other on :value.',
    'required_unless'      => ':attribute-kenttä on pakollinen, ellei :other ole :values.',
    'required_with'        => ':attribute-kenttä on pakollinen, kun :values on läsnä.',
    'required_with_all'    => ':attribute-kenttä on pakollinen, kun :values on läsnä.',
    'required_without'     => ':attribute-kenttä on pakollinen, kun :values ei ole läsnä.',
    'required_without_all' => ':attribute-kenttä on pakollinen, kun mikään :values ei ole läsnä.',
    'same'                 => ':attribute ja :other täytyy täsmätä.',

    'size' => [
        'numeric' => ':attribute täytyy olla :size.',
        'file'    => ':attribute täytyy olla :size kilotavua.',
        'string'  => ':attribute täytyy olla :size merkkiä.',
        'array'   => ':attribute täytyy sisältää :size kohdetta.',
    ],

    'string'   => ':attribute täytyy olla merkkijono.',
    'timezone' => ':attribute täytyy olla kelvollinen aikavyöhyke.',
    'unique'   => ':attribute on jo käytössä.',
    'uploaded' => ':attribute-lataus epäonnistui.',
    'url'      => ':attribute-muoto on virheellinen.',

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
            'rule-name' => 'räätälöity viesti',
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
