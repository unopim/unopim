<?php

return [
    'acl' => [
        'variant-structures' => 'Estructuras de variantes',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'copia-de-:value',
        'copy-of'                       => 'Copia de :value',
        'variant-already-exist-message' => 'Ya existe una variante con las mismas opciones de atributos.',
    ],
    'response' => [
        'product-can-not-be-copied' => 'Los productos de tipo :type no se pueden copiar',
    ],
    'type' => [
        'configurable'  => 'Configurable',
        'simple'        => 'Simple',
        'variant-group' => 'Grupo de variantes',
        'variant_group' => 'Grupo de variantes',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Se detectó una referencia circular en la plantilla: :path.',
            'currency-not-allowed'    => 'Solo los atributos de precio aceptan una moneda, pero ":attribute" no es un atributo de precio.',
            'currency-not-enabled'    => 'La moneda ":currency" utilizada en la plantilla no está habilitada para ningún canal.',
            'currency-not-in-channel' => 'La moneda ":currency" no está habilitada para el canal ":channel".',
            'filter-failed'           => 'No se pudo aplicar el filtro ":filter" al valor de ":attribute".',
            'filter-not-supported'    => 'El filtro ":filter" no se puede aplicar al atributo ":attribute".',
            'format-date-position'    => 'El filtro format_date debe ser el primer filtro en ":expression".',
            'inside-html-tag'         => 'Las expresiones de plantilla no se pueden colocar dentro de etiquetas o atributos HTML, como enlaces e imágenes.',
            'invalid-filter-argument' => 'El filtro ":filter" en ":expression" tiene un argumento faltante o no válido.',
            'invalid-reference'       => 'La plantilla almacenada en ":attribute" no es válida y no se pudo renderizar.',
            'invalid-syntax'          => 'La expresión de plantilla ":expression" no es válida. Use un código de atributo seguido de filtros opcionales, por ejemplo: name | upper.',
            'max-depth'               => 'Las referencias de plantilla están anidadas en más de :max niveles de profundidad en ":attribute".',
            'missing-value'           => 'El atributo ":attribute" no tiene valor para este canal e idioma.',
            'not-in-family'           => 'El atributo ":attribute" utilizado en la plantilla no pertenece a la familia del producto.',
            'output-too-large'        => 'La plantilla renderizada supera los :max bytes y se ha recortado.',
            'self-reference'          => 'La plantilla no puede hacer referencia a su propio atributo ":attribute".',
            'single-brace'            => 'Se encontró ":text" entre llaves simples. Las expresiones de plantilla requieren llaves dobles.',
            'source-too-long'         => 'Una plantilla puede tener como máximo :max bytes.',
            'too-complex'             => 'La plantilla es demasiado compleja para comprobarla. Acórtala o simplifícala.',
            'too-many-expressions'    => 'Una plantilla puede contener como máximo :max expresiones.',
            'too-many-filters'        => 'La expresión de plantilla ":expression" puede usar como máximo :max filtros.',
            'unclosed-expression'     => 'Se abrió una expresión de plantilla, pero nunca se cerró.',
            'unknown-attribute'       => 'El atributo ":attribute" utilizado en la plantilla no existe.',
            'unknown-currency'        => 'La moneda ":currency" utilizada en la plantilla no existe.',
            'unknown-filter'          => 'El filtro ":filter" no es compatible. Filtros disponibles: :filters.',
            'unsupported-attribute'   => 'El atributo ":attribute" no se puede usar en una plantilla porque su tipo no es compatible.',
        ],
    ],
];
