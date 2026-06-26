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
        'locales' => [
            'title'      => 'Idiomas',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'El código de idioma \'%s\' ya ha sido importado en este lote.',
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
        'export-too-large' => 'Esta exportación es demasiado grande para ejecutarse: se estiman :rows filas × :columns columnas (~:estimated), que superan el espacio disponible (~:available). Reduzca la exportación seleccionando menos canales/idiomas (y atributos) e inténtelo de nuevo.',
        'fields'           => [
            'file-format'         => 'Formato de archivo',
            'with-media'          => 'Con medios',
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
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Estado',
            'enable'         => 'Habilitado',
            'all'            => 'Todos',
        ],
        'products' => [
            'title'              => 'Productos',
            'invalid-locales'    => 'No todos los idiomas seleccionados están disponibles para los canales seleccionados.',
            'invalid-currencies' => 'No todas las monedas seleccionadas están disponibles para los canales seleccionados.',
            'filters'            => [
                'channels'             => 'Canales',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Monedas',
                'currencies-info'      => 'Los atributos de precio se exportan por cada moneda seleccionada. Déjalo vacío para exportar todas las monedas del canal.',
                'locales'              => 'Idiomas',
                'locales-info'         => 'Los atributos localizables se exportan una vez por cada idioma seleccionado. Déjalo vacío para exportar todos los idiomas del canal.',
                'attributes'           => 'Atributos',
                'attributes-info'      => 'Solo se exportan los atributos seleccionados. Déjalo vacío para exportar todos los atributos de la familia.',
                'attribute-families'   => 'Familias de atributos',
                'categories'           => 'Categorías',
                'completeness'         => 'Completitud',
                'completeness-options' => [
                    'none'         => 'Sin condición de completitud',
                    'at-least-one' => 'Completo en al menos un idioma seleccionado',
                    'all'          => 'Completo en todos los idiomas seleccionados',
                ],
                'time-condition' => 'Condición de tiempo',
                'time-options'   => [
                    'none'              => 'Sin condición de fecha',
                    'last-n-days'       => 'Productos actualizados en los últimos N días',
                    'between-dates'     => 'Productos actualizados entre dos fechas',
                    'since-last-export' => 'Productos actualizados desde la última exportación',
                ],
                'time-value'     => 'Número de días',
                'time-date'      => 'Fecha de inicio',
                'time-date-end'  => 'Fecha de fin',
                'status'         => 'Estado',
                'status-options' => [
                    'enable'  => 'Habilitado',
                    'disable' => 'Deshabilitado',
                    'all'     => 'Todos',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificadores',
                'identifiers-info' => 'Pega un SKU / identificador por línea para exportar solo esos productos. Déjalo vacío para exportar todos los productos.',
            ],
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
                'status' => 'Estado',
                'active' => 'Active',
                'all'    => 'Todos',
            ],
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
            'file-empty'           => 'El archivo está vacío o no contiene una fila de encabezado. Por favor, suba un archivo válido con datos.',
        ],
    ],
    'job' => [
        'started'   => 'La ejecución del trabajo ha comenzado.',
        'completed' => 'Ejecución del trabajo completada',
    ],
];
