<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicación',
            'info'     => 'Capa de servicio público para contenido publicado por idioma.',
            'settings' => [
                'title'      => 'Ajustes de publicación',
                'enabled'    => 'Habilitado',
                'base-url'   => 'URL base',
                'cache-ttl'  => 'TTL de caché (segundos)',
                'rate-limit' => 'Límite de solicitudes (peticiones/minuto)',
                'indexable'  => 'Permitir la indexación en motores de búsqueda',
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
            'heading' => 'Demasiadas solicitudes. Inténtalo de nuevo en unos momentos.',
        ],
        'withdrawn' => [
            'heading' => 'Este pasaporte ya no está disponible.',
        ],
    ],
];
