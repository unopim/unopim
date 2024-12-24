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
                    'super-attribute-not-found'                => 'Atributo configurable con código: \'%s\' no encontrado o no pertenece a la familia de atributos: \'%s\'',
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
        ],
    ],
    'job' => [
        'started'   => 'Trabajo iniciado',
        'completed' => 'Trabajo completado',
    ],
];
