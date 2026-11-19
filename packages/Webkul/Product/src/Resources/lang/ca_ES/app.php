<?php

return [
    'acl' => [
        'variant-structures' => 'Estructures de variants',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'còpia-de-:value',
        'copy-of'                       => 'Còpia de :value',
        'variant-already-exist-message' => 'Ja existeix un variant amb les mateixes opcions d\'atribut.',
    ],
    'response' => [
        'product-can-not-be-copied' => 'Els productes de tipus :type no es poden copiar',
    ],
    'type' => [
        'configurable'  => 'Configurat',
        'simple'        => 'Simple',
        'variant-group' => 'Grup de variants',
        'variant_group' => 'Grup de variants',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'S\'ha detectat una referència circular a la plantilla: :path.',
            'currency-not-allowed'    => 'Només els atributs de preu accepten una moneda, però ":attribute" no és un atribut de preu.',
            'currency-not-enabled'    => 'La moneda ":currency" utilitzada a la plantilla no està habilitada per a cap canal.',
            'currency-not-in-channel' => 'La moneda ":currency" no està habilitada per al canal ":channel".',
            'filter-failed'           => 'No s\'ha pogut aplicar el filtre ":filter" al valor de ":attribute".',
            'filter-not-supported'    => 'El filtre ":filter" no es pot aplicar a l\'atribut ":attribute".',
            'format-date-position'    => 'El filtre format_date ha de ser el primer filtre a ":expression".',
            'inside-html-tag'         => 'Les expressions de plantilla no es poden col·locar dins d\'etiquetes o atributs HTML, com ara enllaços i imatges.',
            'invalid-filter-argument' => 'El filtre ":filter" a ":expression" té un argument absent o no vàlid.',
            'invalid-reference'       => 'La plantilla emmagatzemada a ":attribute" no és vàlida i no s\'ha pogut renderitzar.',
            'invalid-syntax'          => 'L\'expressió de plantilla ":expression" no és vàlida. Utilitzeu un codi d\'atribut seguit de filtres opcionals, per exemple: name | upper.',
            'max-depth'               => 'Les referències de plantilla estan imbricades més de :max nivells de profunditat a ":attribute".',
            'missing-value'           => 'L\'atribut ":attribute" no té cap valor per a aquest canal i aquesta localització.',
            'not-in-family'           => 'L\'atribut ":attribute" utilitzat a la plantilla no pertany a la família del producte.',
            'output-too-large'        => 'La plantilla renderitzada supera els :max bytes i ha estat retallada.',
            'self-reference'          => 'La plantilla no pot fer referència al seu propi atribut ":attribute".',
            'single-brace'            => 'S\'ha trobat ":text" entre claus simples. Les expressions de plantilla necessiten claus dobles.',
            'source-too-long'         => 'Una plantilla pot tenir com a màxim :max bytes.',
            'too-complex'             => 'La plantilla és massa complexa per comprovar-la. Escurça-la o simplifica-la.',
            'too-many-expressions'    => 'Una plantilla pot contenir com a màxim :max expressions.',
            'too-many-filters'        => 'L\'expressió de plantilla ":expression" pot utilitzar com a màxim :max filtres.',
            'unclosed-expression'     => 'S\'ha obert una expressió de plantilla, però no s\'ha tancat mai.',
            'unknown-attribute'       => 'L\'atribut ":attribute" utilitzat a la plantilla no existeix.',
            'unknown-currency'        => 'La moneda ":currency" utilitzada a la plantilla no existeix.',
            'unknown-filter'          => 'El filtre ":filter" no és compatible. Filtres disponibles: :filters.',
            'unsupported-attribute'   => 'L\'atribut ":attribute" no es pot utilitzar en una plantilla perquè el seu tipus no és compatible.',
        ],
    ],
];
