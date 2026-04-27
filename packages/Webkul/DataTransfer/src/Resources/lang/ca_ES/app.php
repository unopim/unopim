<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Productes',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Clau URL: \'%s\' ja ha estat generada per un element amb l\'SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valor incorrecte per a la família d\'atributs (la família d\'atributs no existeix?).',
                    'invalid-type'                             => 'Tipus de producte no vàlid o no compatible',
                    'sku-not-found'                            => 'Producte amb l\'SKU especificat no trobat',
                    'super-attribute-not-found'                => 'L\'atribut configurable amb codi: \'%s\' no trobat o no pertany a la família d\'atributs: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Són necessaris els atributs configurables per crear el model de producte',
                    'configurable-attributes-wrong-type'       => 'Només els atributs del tipus seleccionat que no siguin basats en la localització o el canal són permetuts com a atributs configurables per a un producte configurable',
                    'variant-configurable-attribute-not-found' => 'L\'atribut configurable variant: :code és necessari per crear',
                    'not-unique-variant-product'               => 'Ja existeix un producte amb els mateixos atributs configurables.',
                    'channel-not-exist'                        => 'Aquest canal no existeix.',
                    'locale-not-in-channel'                    => 'Aquesta localització no està seleccionada al canal.',
                    'locale-not-exist'                         => 'Aquesta localització no existeix',
                    'not-unique-value'                         => 'El valor :code ha de ser únic.',
                    'incorrect-family-for-variant'             => 'La família ha de ser la mateixa que la família parent',
                    'parent-not-exist'                         => 'El parent no existeix.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categories',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'No pots eliminar la categoria arrel associada amb un canal',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Atributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'El codi d\'atribut :code ja està en ús.',
                    'code_not_found_to_delete'             => 'Codi d\'atribut no trobat per a supressió.',
                    'code_is_system_and_cannot_be_deleted' => 'L\'atribut del sistema no es pot eliminar.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grups d\'Atributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'El codi de grup d\'atributs :code ja està en ús.',
                    'code_not_found_to_delete'             => 'Codi de grup d\'atributs no trobat per a supressió.',
                    'code_is_system_and_cannot_be_deleted' => 'El grup d\'atributs del sistema no es pot eliminar.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Famílies d\'Atributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El codi de família d\'atributs :code ja està en ús.',
                    'code_not_found_to_delete' => 'Codi de família d\'atributs no trobat per a supressió.',
                    'invalid-attribute-group'  => 'El grup d\'atributs ":code" no existeix.',
                    'invalid-attribute'        => 'L\'atribut ":code" no existeix.',
                    'invalid-channel'          => 'El canal ":code" no existeix.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opcions d\'Atribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El codi d\'opció d\'atribut :code ja està en ús.',
                    'code_not_found_to_delete' => 'Codi d\'opció d\'atribut no trobat per a supressió.',
                    'locale-not-exist'         => 'L\'idioma ":code" no existeix.',
                    'invalid-attribute'        => 'L\'atribut ":code" no existeix.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Productes',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Clau URL: \'%s\' ja ha estat generada per un element amb l\'SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valor incorrecte per a la família d\'atributs (la família d\'atributs no existeix?)',
                    'invalid-type'              => 'Tipus de producte no vàlid o no compatible',
                    'sku-not-found'             => 'Producte amb l\'SKU especificat no trobat',
                    'super-attribute-not-found' => 'L\'atribut superior amb codi: \'%s\' no trobat o no pertany a la família d\'atributs: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categories',
        ],
        'attributes' => [
            'title' => 'Atributs',
        ],
        'attribute-groups' => [
            'title' => 'Grups d\'Atributs',
        ],
        'attribute-families' => [
            'title' => 'Famílies d\'Atributs',
        ],
        'attribute-options' => [
            'title' => 'Opcions d\'Atribut',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Les columnes número "%s" tenen cap capçalera buida.',
            'column-name-invalid'  => 'Noms de columnes invàlids: "%s".',
            'column-not-found'     => 'Columnes requerides no trobades: %s.',
            'column-numbers'       => 'El nombre de columnes no correspon al nombre de files a la capçalera.',
            'invalid-attribute'    => 'Capçalera conté atribut(s) invàlid(s): "%s".',
            'system'               => 'S\'ha produït un error de sistema inesperat.',
            'wrong-quotes'         => 'S\'han utilitzat guions curts en lloc de guions rectes.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Inici de l\'execució de la feina',
        'completed' => 'Finalització de l\'execució de la feina',
    ],
];
