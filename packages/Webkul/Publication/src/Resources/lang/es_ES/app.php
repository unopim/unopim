<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicación',
            'info'     => 'Capa de servicio público para contenido publicado por idioma.',
            'settings' => [
                'title'      => 'Configuración de publicación',
                'enabled'    => 'Activado',
                'base-url'   => 'URL base',
                'cache-ttl'  => 'TTL de caché (segundos)',
                'rate-limit' => 'Límite de velocidad (solicitudes/minuto)',
                'indexable'  => 'Permitir la indexación por motores de búsqueda',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Borrador',
            'published' => 'Publicado',
            'withdrawn' => 'Retirado',
            'redacted'  => 'Redactado',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Pasaporte no encontrado.',
        ],
        '429' => [
            'heading' => 'Demasiadas solicitudes. Inténtelo de nuevo en breve.',
        ],
        'withdrawn' => [
            'heading' => 'Este pasaporte ya no está disponible.',
        ],
    ],
];
