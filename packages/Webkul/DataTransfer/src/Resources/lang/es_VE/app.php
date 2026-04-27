<?php

return [
    'importers' => [
        'products' => [
            'title' => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'Clave URL: \'%s\' ya se ha generado para un artículo con SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type' => 'El tipo de producto no es válido o no está soportado',
                    'sku-not-found' => 'No se encontró el producto con SKU especificado',
                    'super-attribute-not-found' => 'Atributo configurable con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found' => 'Se requieren atributos configurables para crear el modelo de producto',
                    'configurable-attributes-wrong-type' => 'Solo los atributos de tipo que no se basan en la ubicación o el canal pueden ser atributos configurables para un producto configurable',
                    'variant-configurable-attribute-not-found' => 'Atributo configurable de variante: :code es necesario para crear',
                    'not-unique-variant-product' => 'Ya existe un producto con los mismos atributos configurables.',
                    'channel-not-exist' => 'Este canal no existe.',
                    'locale-not-in-channel' => 'Este idioma no está seleccionado en el canal.',
                    'locale-not-exist' => 'Este idioma no existe',
                    'not-unique-value' => 'El valor :code debe ser único.',
                    'incorrect-family-for-variant' => 'La familia debe ser la misma que la familia principal',
                    'parent-not-exist' => 'El producto padre no existe.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorías',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'No puedes eliminar la categoría raíz relacionada con un canal',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'El código de atributo :code ya está en uso.',
                    'code_not_found_to_delete'             => 'Código de atributo no encontrado para eliminación.',
                    'code_is_system_and_cannot_be_deleted' => 'El atributo del sistema no se puede eliminar.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grupos de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'El código del grupo de atributos :code ya está en uso.',
                    'code_not_found_to_delete'             => 'Código de grupo de atributos no encontrado para eliminación.',
                    'code_is_system_and_cannot_be_deleted' => 'El grupo de atributos del sistema no se puede eliminar.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Famílies de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El código de la familia de atributos :code ya está en uso.',
                    'code_not_found_to_delete' => 'Código de familia de atributos no encontrado para eliminación.',
                    'invalid-attribute-group'  => 'El grupo de atributos ":code" no existe.',
                    'invalid-attribute'        => 'El atributo ":code" no existe.',
                    'invalid-channel'          => 'El canal ":code" no existe.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opciones de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El código de la opción de atributo :code ya está en uso.',
                    'code_not_found_to_delete' => 'Código de opción de atributo no encontrado para eliminación.',
                    'locale-not-exist'         => 'La configuración regional ":code" no existe.',
                    'invalid-attribute'        => 'El atributo ":code" no existe.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title' => 'Productos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key' => 'Clave URL: \'%s\' ya se ha generado para un artículo con SKU: \'%s\'.',
                    'invalid-attribute-family' => 'Valor no válido para la columna de familia de atributos (¿la familia de atributos no existe?)',
                    'invalid-type' => 'El tipo de producto no es válido o no está soportado',
                    'sku-not-found' => 'No se encontró el producto con SKU especificado',
                    'super-attribute-not-found' => 'Atributo configurable con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorías',
        ],
        'attributes' => [
            'title' => 'Atributos',
        ],
        'attribute-groups' => [
            'title' => 'Grupos de atributos',
        ],
        'attribute-families' => [
            'title' => 'Familias de atributos',
        ],
        'attribute-options' => [
            'title' => 'Opciones de atributos',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Las columnas con número "%s" tienen encabezados vacíos.',
            'column-name-invalid' => 'Nombres de columna no válidos: "%s".',
            'column-not-found' => 'Columnas requeridas no encontradas: %s.',
            'column-numbers' => 'La cantidad de columnas no coincide con la cantidad de filas en el encabezado.',
            'invalid-attribute' => 'El encabezado contiene atributos no válidos: "%s".',
            'system' => 'Ocurrió un error del sistema inesperado.',
            'wrong-quotes' => 'Se utilizaron comillas curvas en lugar de comillas rectas.',
            'file-empty' => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started' => 'Trabajo iniciado',
        'completed' => 'Trabajo completado',
    ],
];
