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
            'title'      => 'Categorías',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'No pots eliminar la categoria arrel associada amb un canal',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Camps de categoria',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El codi del camp de categoria :code ja està en ús.',
                    'code_not_found_to_delete' => 'No s\'ha trobat el codi del camp de categoria per eliminar.',
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
        'locales' => [
            'title'      => 'Idiomes',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'El codi d\'idioma \'%s\' ja s\'ha importat en aquest lot.',
                    'code-not-found-to-delete'    => 'No s\'ha trobat cap idioma amb el codi \'%s\' al sistema.',
                    'invalid-status'              => 'L\'estat ha de ser 0 o 1 (o buit per defecte activat).',
                    'channel-related-locale-root' => 'No podeu eliminar l\'idioma amb el codi :code perquè està associat a un canal.',
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
            'title'   => 'Monedes',
            'filters' => [
                'status' => 'Estat',
                'enable' => 'Activar',
                'all'    => 'Tot',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'L\'estat ha de ser 0 o 1 (o buit per defecte activat).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Rols',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Usuaris',
            'filters' => [
                'status' => 'Estat',
                'active' => 'Actiu',
                'all'    => 'Tot',
            ],
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
        'export-too-large' => 'Aquesta exportació és massa gran per executar-se: s\'estimen :rows files × :columns columnes (~:estimated), que superen l\'espai disponible (~:available). Reduïu l\'exportació seleccionant menys canals/idiomes (i atributs) i torneu-ho a provar.',
        'fields'           => [
            'file-format'         => 'Format de fitxer',
            'with-media'          => 'Amb mèdia',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Camí del Fitxer',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Estat',
            'enable'         => 'Activat',
            'all'            => 'Tots',
        ],
        'products' => [
            'title'              => 'Productes',
            'invalid-locales'    => 'No tots els idiomes seleccionats estan disponibles per als canals seleccionats.',
            'invalid-currencies' => 'No totes les monedes seleccionades estan disponibles per als canals seleccionats.',
            'filters'            => [
                'channels'             => 'Canals',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Monedes',
                'currencies-info'      => 'Els atributs de preu s\'exporten per cada moneda seleccionada. Deixeu-ho buit per exportar totes les monedes del canal.',
                'locales'              => 'Configuracions regionals',
                'locales-info'         => 'Els atributs localitzables s\'exporten una vegada per cada idioma seleccionat. Deixeu-ho buit per exportar tots els idiomes del canal.',
                'attributes'           => 'Atributs',
                'attributes-info'      => 'Només s\'exporten els atributs seleccionats. Deixeu-ho buit per exportar tots els atributs de la família.',
                'attribute-families'   => 'Famílies d\'atributs',
                'categories'           => 'Categorías',
                'completeness'         => 'Completesa',
                'completeness-options' => [
                    'none'         => 'Sense condició de completesa',
                    'at-least-one' => 'Complet en almenys un idioma seleccionat',
                    'all'          => 'Complet en tots els idiomes seleccionats',
                ],
                'time-condition' => 'Condició de temps',
                'time-options'   => [
                    'none'              => 'Sense condició de data',
                    'last-n-days'       => 'Productes actualitzats en els últims N dies',
                    'between-dates'     => 'Productes actualitzats entre dues dates',
                    'since-last-export' => 'Productes actualitzats des de l\'última exportació',
                ],
                'time-value'     => 'Nombre de dies',
                'time-date'      => 'Data d\'inici',
                'time-date-end'  => 'Data de finalització',
                'status'         => 'Estat',
                'status-options' => [
                    'enable'  => 'Activat',
                    'disable' => 'Desactivat',
                    'all'     => 'Tots',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificadors',
                'identifiers-info' => 'Enganxeu un SKU / identificador per línia per exportar només aquests productes. Deixeu-ho buit per exportar tots els productes.',
            ],
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
            'title' => 'Categorías',
        ],
        'category-fields' => [
            'title' => 'Camps de categoria',
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
        'locales' => [
            'title' => 'Idiomes',
        ],
        'channels' => [
            'title' => 'Canals',
        ],
        'currencies' => [
            'title' => 'Monedes',
        ],
        'roles' => [
            'title' => 'Rols',
        ],
        'users' => [
            'title'   => 'Usuaris',
            'filters' => [
                'status' => 'Estat',
                'active' => 'Actiu',
                'all'    => 'Tots',
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
