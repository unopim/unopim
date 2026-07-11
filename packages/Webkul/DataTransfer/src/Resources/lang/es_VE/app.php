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
        'category-fields' => [
            'title'      => 'Campos de categoría',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'El código del campo de categoría :code ya está en uso.',
                    'code_not_found_to_delete' => 'No se encontró el código del campo de categoría para eliminar.',
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
        'product-associations' => [
            'title'      => 'Asociaciones de productos',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'El campo \'%s\' es obligatorio.',
                    'self-link-not-allowed'       => 'El producto \'%s\' no se puede asociar consigo mismo.',
                    'sku-not-found'               => 'No se encontró ningún producto con el SKU \'%s\'.',
                    'related-sku-not-found'       => 'No se encontró el producto relacionado con el SKU \'%s\'.',
                    'association-type-not-found'  => 'El tipo de asociación \'%s\' no existe o está inactivo.',
                    'invalid-field-value'         => 'Valor no válido proporcionado para un campo de asociación.',
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
            'title'   => 'Monedas',
            'filters' => [
                'status' => 'Estado',
                'enable' => 'Activar',
                'all'    => 'Todos',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'El estado debe ser 0 o 1 (o vacío para habilitado por defecto).',
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
            'title'   => 'Usuarios',
            'filters' => [
                'status' => 'Estado',
                'active' => 'Activo',
                'all'    => 'Todos',
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
            'file-path'      => 'Ruta del Archivo',
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
        'category-fields' => [
            'title' => 'Campos de categoría',
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
        'locales' => [
            'title' => 'Idiomas',
        ],
        'channels' => [
            'title' => 'Canales',
        ],
        'currencies' => [
            'title' => 'Monedas',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Usuarios',
            'filters' => [
                'status' => 'Estado',
                'active' => 'Activo',
                'all'    => 'Todos',
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
