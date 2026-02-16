<?php

return [
    'exporters' => [
        'shopify' => [
            'product'  => 'Producto de Shopify',
            'category' => 'Categoría de Shopify',
        ],
    ],
    'importers' => [
        'shopify' => [
            'product'   => 'Producto Shopify',
            'category'  => 'Categoría Shopify',
            'attribute' => 'Atributo Shopify',
            'family'    => 'Familia Shopify',
            'metafield' => 'Definiciones de metacampos de Shopify',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'shopify'         => 'Shopify',
                'credentials'     => 'Credenciales',
                'export-mappings' => 'Mapeos de Exportación',
                'import-mappings' => 'Mapas de importación',
                'settings'        => 'Configuraciones',
            ],
        ],
    ],

    'shopify' => [
        'acl' => [
            'credential' => [
                'create' => 'Crear',
                'edit'   => 'Editar',
                'delete' => 'Eliminar',
            ],

            'metafield' => [
                'create'      => 'Crear Metafield',
                'edit'        => 'Editar Metafield',
                'delete'      => 'Eliminar Metafield',
                'mass_delete' => 'Eliminar Metafield en masa',
            ],
        ],

        'version' => 'Versión: 1.0.0',

        'credential' => [
            'export' => [
                'locales' => 'Mapeo de Locales',
            ],
            'shopify' => [
                'locale' => 'Local de Shopify',
            ],
            'unopim' => [
                'locale' => 'Local de Unopim',
            ],
            'delete-success' => 'Credencial Eliminada con Éxito',
            'created'        => 'Credencial Creada con Éxito',
            'update-success' => 'Actualización Exitosa',
            'invalid'        => 'Credencial Inválida',
            'invalidurl'     => 'URL Inválida',
            'already_taken'  => 'La URL de la tienda ya ha sido tomada.',
            'index'          => [
                'title'                 => 'Credenciales de Shopify',
                'create'                => 'Crear Credencial',
                'url'                   => 'URL de Shopify',
                'shopifyurlplaceholder' => 'URL de Shopify (ej. http://demo.myshopify.com)',
                'accesstoken'           => 'Token de acceso de Admin API',
                'apiVersion'            => 'Versión de API',
                'save'                  => 'Guardar',
                'back-btn'              => 'Volver',
                'channel'               => 'Publicación (Canales de venta)',
                'locations'             => 'Lista de Localizaciones',
            ],
            'edit' => [
                'title'    => 'Editar Credencial',
                'delete'   => 'Eliminar Credencial',
                'back-btn' => 'Volver',
                'update'   => 'Actualizar',
                'save'     => 'Guardar',
            ],
            'datagrid' => [
                'shopUrl'    => 'URL de Shopify',
                'apiVersion' => 'Versión de API',
                'enabled'    => 'Habilitar',
            ],
        ],
        'export' => [
            'mapping' => [
                'title'         => 'Mapeos de Exportación',
                'back-btn'      => 'Volver',
                'save'          => 'Guardar',
                'created'       => 'Mapeo de Exportación Creado',
                'image'         => 'Atributo usado como imagen',
                'metafields'    => 'Atributos usados como Metafields',
                'filed-shopify' => 'Campo en Shopify',
                'attribute'     => 'Atributo',
                'fixed-value'   => 'Valor Fijo',
            ],
            'setting' => [
                'title'                        => 'Configuración',
                'tags'                         => 'Configuración de Exportación de Etiquetas',
                'enable_metric_tags_attribute' => '¿Quieres incluir el nombre de la Unidad Métrica en las etiquetas?',
                'enable_named_tags_attribute'  => '¿Quieres incluir etiquetas nombradas?',
                'tagSeprator'                  => 'Usar Separador de Nombre de Atributo en las Etiquetas',
                'enable_tags_attribute'        => '¿Quieres incluir también el nombre del atributo en las etiquetas?',
                'metafields'                   => 'Configuración de Exportación de Meta Fields',
                'metaFieldsKey'                => 'Usar Clave para Meta Field como Código/Label del Atributo',
                'metaFieldsNameSpace'          => 'Usar Namespace para Meta Field como Código de Grupo de Atributo/global',
                'crednetials'                  => 'Select Credentials',
                'other-settings'               => 'Otras Configuraciones',
                'roundof-attribute-value'      => 'Eliminar Decimales Extras de los Valores Métricos (ej. 201.2000 como 201.2)',
                'option_name_label'            => 'Valor para el Nombre de Opción como Label del Atributo (Por Defecto Código de Atributo)',
            ],

            'errors' => [
                'invalid-credential' => 'Credencial no válida. La credencial está deshabilitada o es incorrecta.',
                'invalid-locale'     => 'Localización no válida. Por favor, mapea la localización en la sección de edición de credenciales',
            ],
        ],
        'import' => [
            'mapping' => [
                'title'                => 'Mapeos de Importación',
                'back-btn'             => 'Volver',
                'save'                 => 'Guardar',
                'created'              => 'El mapeo de importación se guardó correctamente',
                'image'                => 'Atributo utilizado como imagen',
                'filed-shopify'        => 'Campo en Shopify',
                'attribute'            => 'Atributo de UnoPim',
                'variantimage'         => 'Atributo utilizado como imagen de variante',
                'other'                => 'Otros mapeos en Shopify',
                'family'               => 'Mapeo de familia (para productos)',
                'metafieldDefinitions' => 'Mapeo de definiciones de campos personalizados de Shopify',
            ],
            'setting' => [
                'credentialmapping' => 'Mapeo de credenciales',
            ],
            'job' => [
                'product' => [
                    'family-not-exist'      => 'La familia no existe para el título: - :title. Primero necesitas importar la familia',
                    'variant-sku-not-exist' => 'El SKU de la variante no se encontró en el producto: - :id',
                    'duplicate-sku'         => ':sku : - Se encontró un SKU duplicado en el producto',
                    'required-field'        => ':attribute : - El campo es obligatorio para el SKU: - :sku',
                    'family-not-mapping'    => 'La familia no está mapeada para el título: - :title',
                    'attribute-not-exist'   => 'Los atributos :attributes no existen para el producto',
                    'not-found-sku'         => 'El SKU no se encontró en el producto: - :id',
                    'option-not-found'      => ':attribute - :option La opción no se encuentra en el SKU de UnoPim: - :sku',
                ],
            ],
        ],

        'fields' => [
            'name'                        => 'Nombre',
            'description'                 => 'Descripción',
            'price'                       => 'Precio',
            'weight'                      => 'Peso',
            'quantity'                    => 'Cantidad',
            'inventory_tracked'           => 'Inventario Rastreador',
            'allow_purchase_out_of_stock' => 'Permitir Compra sin Stock',
            'vendor'                      => 'Proveedor',
            'product_type'                => 'Tipo de Producto',
            'tags'                        => 'Etiquetas',
            'barcode'                     => 'Código de Barras',
            'compare_at_price'            => 'Comparar Precio',
            'seo_title'                   => 'Título SEO',
            'seo_description'             => 'Descripción SEO',
            'handle'                      => 'Manejar',
            'taxable'                     => 'Gravable',
            'inventory_cost'              => 'Costo de Inventario',
        ],
        'exportmapping' => 'Mapeo de Atributos',
        'job'           => [
            'credentials'      => 'Credenciales de Shopify',
            'channel'          => 'Canal',
            'currency'         => 'Moneda',
            'productfilter'    => 'Filtro de productos (SKU)',
            'locale'           => 'Idioma',
            'attribute-groups' => 'Grupos de atributos',
        ],
        'metafield'     => [
            'datagrid' => [
                'definitiontype'  => 'Usado para',
                'attribute-label'  => 'Atributo Unopim',
                'definitionName'  => 'Nombre de la definición',
                'contentTypeName' => 'Tipo',
                'pin'             => 'Pin',
            ],
            'index'    => [
                'title'                     => 'Definiciones de Metacampo',
                'create'                    => 'Agregar definición',
                'definitiontype'            => 'Usado para',
                'attribute'                 => 'Atributo UnoPim',
                'ContentTypeName'           => 'Tipo',
                'attributes'                => 'Nombre de la definición',
                'urlvalidation'             => 'Validación',
                'urlvalidationdata'         => 'Los valores deben estar precedidos por: "HTTPS", "HTTP", "mailto:", "sms:" o "tel:"',
                'name_space_key'            => 'Espacio de nombres y clave',
                'description'               => 'Descripción',
                'onevalue'                  => 'Un valor',
                'listvalue'                 => 'Lista de valores',
                'validation'                => 'Validaciones',
                'maxvalue'                  => 'Valor máximo',
                'adminFilterable'           => 'Filtrado para productos',
                'smartCollectionCondition'  => 'Colecciones inteligentes',
                'storefronts'               => 'Acceso a escaparates',
            ],

            'type' => [
                'single_line_text_field' => 'Texto de una línea',
                'color'                  => 'Color',
                'rating'                 => 'Calificación',
                'url'                    => 'URL',
                'multi_line_text_field'  => 'Texto de varias líneas',
                'json'                   => 'JSON',
                'boolean'                => 'Verdadero o falso',
                'date'                   => 'Fecha',
                'number_decimal'         => 'Número decimal',
                'number_integer'         => 'Número entero',
                'dimension'              => 'Dimensión',
                'weight'                 => 'Peso',
                'volume'                 => 'Volumen',
            ],

            'edit'     => [
                'title'           => 'Editar definición de Metacampo',
                'back-btn'        => 'Atrás',
                'update'          => 'Actualizar',
                'save'            => 'Guardar',
            ],
            'delete-success'      => 'Definición de Metacampo eliminada con éxito',
            'update-success'      => 'Definición de Metacampo actualizada con éxito',
            'created'             => 'Definición de Metacampo creada con éxito',
            'mass-delete-success' => 'Definiciones de Metacampo eliminadas con éxito',
        ],
    ],
];
