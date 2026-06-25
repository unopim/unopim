<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Clave URL: \'%s\' ya se ha generado para un artículo con SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type'                             => 'El tipo de producto no es válido o no está soportado',
                    'sku-not-found'                            => 'No se encontró el producto con SKU especificado',
                    'super-attribute-not-found'                => 'Atributo configurable con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Se requieren atributos configurables para crear el modelo de producto',
                    'configurable-attributes-wrong-type'       => 'Solo los atributos de tipo que no se basan en la ubicación o el canal pueden ser atributos configurables para un producto configurable',
                    'variant-configurable-attribute-not-found' => 'Atributo configurable de variante: :code es necesario para crear',
                    'not-unique-variant-product'               => 'Ya existe un producto con los mismos atributos configurables.',
                    'channel-not-exist'                        => 'Este canal no existe.',
                    'locale-not-in-channel'                    => 'Este idioma no está seleccionado en el canal.',
                    'locale-not-exist'                         => 'Este idioma no existe',
                    'not-unique-value'                         => 'El valor :code debe ser único.',
                    'incorrect-family-for-variant'             => 'La familia debe ser la misma que la familia principal',
                    'parent-not-exist'                         => 'El producto padre no existe.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorías',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'No puedes eliminar la categoría raíz relacionada con un canal',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Idiomas',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'El código de idioma \'%s\' ya fue importado en este lote.',
                    'code-not-found-to-delete'    => 'No se encontró un idioma con el código \'%s\' en el sistema.',
                    'invalid-status'              => 'El estado debe ser 0 o 1 (o vacío para habilitado por defecto).',
                    'channel-related-locale-root' => 'No puedes eliminar el idioma con código :code porque está asociado a un canal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canales',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Canal con código :code no encontrado para eliminar.',
                    'locale-not-found'         => 'Uno o más idiomas no existen.',
                    'root-category-not-found'  => 'La categoría raíz no existe.',
                    'currency-not-found'       => 'Una o más monedas no existen.',
                    'invalid-locale'           => 'El idioma no existe.',
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
            'title'      => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Clave URL: \'%s\' ya se ha generado para un artículo con SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type'              => 'El tipo de producto no es válido o no está soportado',
                    'sku-not-found'             => 'No se encontró el producto con SKU especificado',
                    'super-attribute-not-found' => 'Atributo configurable con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorías',
        ],
        'locales' => [
            'title' => 'Idiomas',
        ],
        'channels' => [
            'title' => 'Canales',
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
            'column-empty-headers' => 'Las columnas con número "%s" tienen encabezados vacíos.',
            'column-name-invalid'  => 'Nombres de columna no válidos: "%s".',
            'column-not-found'     => 'Columnas requeridas no encontradas: %s.',
            'column-numbers'       => 'La cantidad de columnas no coincide con la cantidad de filas en el encabezado.',
            'invalid-attribute'    => 'El encabezado contiene atributos no válidos: "%s".',
            'system'               => 'Ocurrió un error del sistema inesperado.',
            'wrong-quotes'         => 'Se utilizaron comillas curvas en lugar de comillas rectas.',
            'file-empty'           => 'El archivo está vacío o no contiene una fila de encabezado. Por favor, suba un archivo válido con datos.',
        ],
    ],
    'job' => [
        'started'   => 'Trabajo iniciado',
        'completed' => 'Trabajo completado',
    ],
];
