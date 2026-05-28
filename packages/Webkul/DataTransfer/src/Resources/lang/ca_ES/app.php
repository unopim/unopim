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
        'channels' => [
            'title'      => 'Canals',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'El canal amb el codi :code no s\'ha trobat per eliminar.',
                    'locale-not-found'         => 'Un o més idiomes no existeixen.',
                    'root-category-not-found'  => 'La categoria arrel no existeix.',
                    'currency-not-found'       => 'Una o més monedes no existeixen.',
                    'invalid-locale'           => 'L\'idioma no existeix.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'channels' => [
            'title' => 'Canals',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
            'file-empty'           => 'El fitxer està buit o no conté una fila de capçalera. Si us plau, pengeu un fitxer vàlid amb dades.',
        ],
    ],
    'job' => [
        'started'   => 'Inici de l\'execució de la feina',
        'completed' => 'Finalització de l\'execució de la feina',
    ],
];
