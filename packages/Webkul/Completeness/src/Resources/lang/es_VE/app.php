<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completitud',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completitud actualizada exitosamente',
                    'title'               => 'Completitud',
                    'configure'           => 'Configurar completitud',
                    'channel-required'    => 'Requerido en canales',
                    'save-btn'            => 'Guardar',
                    'back-btn'            => 'Volver',
                    'mass-update-success' => 'Completitud actualizada exitosamente',
                    'datagrid'            => [
                        'code'             => 'Código',
                        'name'             => 'Nombre',
                        'channel-required' => 'Requerido en canales',
                        'actions'          => [
                            'change-requirement' => 'Cambiar requisito de completitud',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Completo',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Completitud',
                    'subtitle' => 'Completitud promedio',
                ],
                'required-attributes' => 'atributos requeridos faltantes',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Cálculo de completitud finalizado',
        'completeness-calculated'        => 'Completitud calculada para :count productos.',
        'completeness-calculated-family' => 'Completitud calculada para :count productos en la familia ":family".',
        'email-subject'                  => 'Cálculo de completitud finalizado',
        'email-greeting'                 => 'Hola,',
        'email-body'                     => 'El cálculo de completitud se ha completado para :count productos.',
        'email-body-family'              => 'El cálculo de completitud se ha completado para :count productos en la familia de atributos ":family".',
        'email-footer'                   => 'Puede ver los detalles de completitud en su panel de control.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Productos calculados',
                'suggestion'          => [
                    'low'     => 'Completitud baja, agregue detalles para mejorar.',
                    'medium'  => 'Siga adelante, continúe agregando información.',
                    'high'    => 'Casi completo, solo faltan unos pocos detalles.',
                    'perfect' => 'La información del producto está completamente completa.',
                ],
            ],
        ],
    ],
];
