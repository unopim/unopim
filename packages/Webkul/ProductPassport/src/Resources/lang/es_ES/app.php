<?php

return [
    'type' => [
        'label' => 'Pasaporte Digital del Producto',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Pasaporte del Producto',
            'info'     => 'Ajustes de publicación del pasaporte digital del producto.',
            'settings' => [
                'title'                              => 'Configuración del pasaporte del producto',
                'enabled'                            => 'Activado',
                'enabled-hint'                       => 'Activa la función de Pasaporte Digital de Producto para este catálogo. Cuando está desactivada, el panel y la cuadrícula de pasaportes se ocultan.',
                'auto-publish'                       => 'Publicar automáticamente al guardar',
                'auto-publish-hint'                  => 'Publica automáticamente una versión del pasaporte cada vez que se guarda un producto y alcanza el umbral de completitud. Déjelo desactivado para publicar manualmente.',
                'completeness-threshold'             => 'Umbral de completitud (%)',
                'completeness-threshold-hint'        => 'Completitud mínima del producto, en porcentaje, requerida antes de que se pueda publicar un pasaporte para una configuración regional.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Nombre del operador económico',
                'operator-name-hint'                 => 'Nombre legal del fabricante o del operador económico responsable, mostrado en cada pasaporte público según exige el reglamento ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Dirección del operador económico',
                'operator-address-hint'              => 'Dirección postal registrada del operador económico, mostrada en el pasaporte público para la trazabilidad.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Representante autorizado en la UE',
                'operator-eu-rep-hint'               => 'Nombre y contacto del representante autorizado en la UE, requerido cuando el fabricante está establecido fuera de la UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL de soporte',
                'support-url-hint'                   => 'Página pública donde los clientes pueden encontrar ayuda o información de garantía. Se muestra como un enlace en cada pasaporte.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Pasaporte Digital del Producto',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Composición del material',
        'dpp_substances_of_concern'     => 'Sustancias preocupantes',
        'dpp_recycled_content_pct'      => 'Contenido reciclado (%)',
        'dpp_carbon_footprint'          => 'Huella de carbono',
        'dpp_energy_consumption'        => 'Consumo de energía',
        'dpp_durability_statement'      => 'Declaración de durabilidad',
        'dpp_repairability_score'       => 'Puntuación de reparabilidad',
        'dpp_spare_parts_availability'  => 'Disponibilidad de piezas de repuesto',
        'dpp_care_instructions'         => 'Instrucciones de cuidado',
        'dpp_disassembly_guide'         => 'Guía de desmontaje',
        'dpp_manufacturer_name'         => 'Nombre del fabricante',
        'dpp_manufacturing_site'        => 'Lugar de fabricación',
        'dpp_country_of_origin'         => 'País de origen',
        'dpp_supply_chain_notes'        => 'Notas de la cadena de suministro',
        'dpp_end_of_life_instructions'  => 'Instrucciones de fin de vida',
        'dpp_take_back_scheme'          => 'Programa de devolución',
        'dpp_declaration_of_conformity' => 'Declaración de conformidad',
        'dpp_test_reports'              => 'Informes de pruebas',
        'dpp_certificates'              => 'Certificados',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identificador de modelo',
        'dpp_batch_identifier'          => 'Identificador de lote',
        'dpp_warranty_terms'            => 'Condiciones de garantía',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Los atributos del Pasaporte Digital del Producto se instalaron correctamente.',
        ],
    ],

    'public' => [
        'badge'         => 'Pasaporte Digital de Producto EU',
        'search-locale' => 'Idioma de búsqueda',
        'sections'      => [
            'passport' => 'Pasaporte del Producto',
        ],
        'title'      => 'Pasaporte Digital del Producto',
        'identifier' => [
            'title'        => 'Identificación',
            'gtin'         => 'GTIN',
            'model'        => 'Modelo',
            'batch'        => 'Lote',
            'not-provided' => 'No proporcionado',
        ],
        'operator' => [
            'title' => 'Operador económico',
        ],
        'documents' => [
            'title' => 'Documentos',
        ],
    ],

    'publications' => [
        'not-found'      => 'No se encontró ningún pasaporte con el id :id.',
        'index'          => [
            'disabled-notice' => 'La publicación de pasaportes está desactivada actualmente. Los pasaportes existentes se muestran a continuación para su gestión (ver y retirar).',
            'title'           => 'Pasaportes Digitales del Producto',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Canal',
            'status'          => 'Estado',
            'live-locales'    => 'Idiomas activos',
            'last-published'  => 'Última publicación',
            'withdraw'        => 'Retirar',
            'mass-publish'    => 'Publicar seleccionados',
        ],
        'publish-queued'      => 'Se ha puesto en cola la publicación del pasaporte.',
        'bulk-publish-queued' => 'Se ha puesto en cola la publicación de los pasaportes seleccionados.',
        'withdrawn'           => 'Pasaporte retirado correctamente.',
        'mass-publish'        => [
            'action' => 'Publicar el pasaporte digital de producto',
            'queued' => 'Publicación del pasaporte en cola para :count producto(s).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pasaportes',
            'view'     => 'Ver',
            'publish'  => 'Publicar',
            'withdraw' => 'Retirar',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pasaportes',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Publicando…',
                    'queued'               => 'En cola',
                    'copy-operator-link'   => 'Copiar enlace de operador',
                    'copy-authority-link'  => 'Copiar enlace de autoridad',
                    'link-copied'          => 'Enlace copiado',
                    'download-qr'          => 'Descargar código QR',
                    'title'                => 'Pasaporte Digital del Producto',
                    'publishing-disabled'  => 'La publicación de pasaportes está deshabilitada para este canal.',
                    'locale'               => 'Idioma',
                    'version'              => 'Versión',
                    'published-at'         => 'Publicado el',
                    'missing-fields'       => 'Campos pendientes',
                    'not-published'        => 'No publicado',
                    'unscored'             => 'Sin puntuar',
                    'publish'              => 'Publicar',
                    'republish'            => 'Volver a publicar',
                    'publish-all'          => 'Publicar todos los idiomas',
                    'auto-publish-on'      => 'La publicación automática está activada — los pasaportes se publican automáticamente cuando el producto se guarda y alcanza el umbral de completitud. Usa los botones para publicar ahora.',
                    'auto-publish-off'     => 'Publicación manual — usa los botones para publicar el pasaporte de este producto para cada idioma.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'El :attribute debe ser un GTIN válido (8, 12, 13 o 14 dígitos con un dígito de control correcto).',
    ],
    'mapping' => [
        'title'         => 'Asignación de campos del pasaporte',
        'info'          => 'Obtenga cada campo del pasaporte a partir de un atributo que ya mantiene. Deje un campo sin asignar para recurrir a su atributo de pasaporte específico.',
        'menu'          => 'Asignación de campos',
        'field'         => 'Campo del pasaporte',
        'source'        => 'Atributo de origen',
        'select-source' => 'Usar el atributo del pasaporte',
        'save-btn'      => 'Guardar asignación',
        'type-mismatch' => 'La fuente seleccionada no es compatible con el tipo de este campo del pasaporte.',
        'saved'         => 'La asignación de campos se guardó correctamente.',
    ],

];
