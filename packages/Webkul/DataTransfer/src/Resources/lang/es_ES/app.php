<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'La clave URL: \'%s\' ya se generó para un artículo con el SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type'                             => 'El tipo de producto no es válido o no es compatible',
                    'sku-not-found'                            => 'Producto con SKU especificado no encontrado',
                    'super-attribute-not-found'                => 'Atributo configurable con código :code no encontrado o no pertenece a la familia de atributos :familyCode',
                    'configurable-attributes-not-found'        => 'Se requieren atributos configurables para crear el modelo de producto',
                    'configurable-attributes-wrong-type'       => 'Sólo los atributos de tipo seleccionado que no están basados ​​en la configuración regional o en el canal pueden ser atributos configurables para un producto configurable.',
                    'variant-configurable-attribute-not-found' => 'Atributo configurable variante se requiere :code para crear',
                    'not-unique-variant-product'               => 'Ya existe un producto con los mismos atributos configurables.',
                    'channel-not-exist'                        => 'Este canal no existe.',
                    'locale-not-in-channel'                    => 'Esta configuración regional no está seleccionada en el canal.',
                    'locale-not-exist'                         => 'Esta localidad no existe',
                    'not-unique-value'                         => 'El valor :code debe ser único.',
                    'incorrect-family-for-variant'             => 'La familia debe ser la misma que la familia principal.',
                    'parent-not-exist'                         => 'El padre no existe.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorías',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'No se puede eliminar la categoría raíz asociada a un canal.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attributes',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribute code :code is already in use.',
                    'code_not_found_to_delete'             => 'Attribute code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute cannot be deleted.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attribute Groups',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attribute group code :code is already in use.',
                    'code_not_found_to_delete'             => 'Attribute group code not found for deletion.',
                    'code_is_system_and_cannot_be_deleted' => 'System attribute group cannot be deleted.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attribute Families',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribute family code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute family code not found for deletion.',
                    'invalid-attribute-group'  => 'Attribute group ":code" does not exist.',
                    'invalid-attribute'        => 'Attribute ":code" does not exist.',
                    'invalid-channel'          => 'Channel ":code" does not exist.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attribute Options',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attribute option code :code is already in use.',
                    'code_not_found_to_delete' => 'Attribute option code not found for deletion.',
                    'locale-not-exist'         => 'Locale ":code" does not exist.',
                    'invalid-attribute'        => 'Attribute ":code" does not exist.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'La clave URL: \'%s\' ya se generó para un artículo con el SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type'              => 'El tipo de producto no es válido o no es compatible',
                    'sku-not-found'             => 'Producto con SKU especificado no encontrado',
                    'super-attribute-not-found' => 'Súper atributo con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorías',
        ],
        'attributes' => [
            'title' => 'Attributes',
        ],
        'attribute-groups' => [
            'title' => 'Attribute Groups',
        ],
        'attribute-families' => [
            'title' => 'Attribute Families',
        ],
        'attribute-options' => [
            'title' => 'Attribute Options',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Las columnas número "%s" tienen encabezados vacíos.',
            'column-name-invalid'  => 'Nombres de columnas no válidos: "%s".',
            'column-not-found'     => 'Columnas obligatorias no encontradas: %s.',
            'column-numbers'       => 'El número de columnas no corresponde al número de filas del encabezado.',
            'invalid-attribute'    => 'El encabezado contiene atributos no válidos: "%s".',
            'system'               => 'Se produjo un error inesperado del sistema.',
            'wrong-quotes'         => 'Se utilizan comillas rizadas en lugar de comillas rectas.',
        ],
    ],
    'job' => [
        'started'   => 'La ejecución del trabajo ha comenzado.',
        'completed' => 'Ejecución del trabajo completada',
    ],
];
