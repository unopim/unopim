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
                'title'                  => 'Configuración del pasaporte del producto',
                'enabled'                => 'Activado',
                'auto-publish'           => 'Publicar automáticamente al guardar',
                'completeness-threshold' => 'Umbral de completitud (%)',
                'operator-name'          => 'Nombre del operador económico',
                'operator-address'       => 'Dirección del operador económico',
                'operator-eu-rep'        => 'Representante autorizado en la UE',
                'support-url'            => 'URL de soporte',
            ],
        ],
    ],
];
