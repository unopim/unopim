<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicación',
            'info'     => 'Capa de servicio público para contenido publicado por idioma.',
            'settings' => [
                'title'                            => 'Ajustes de publicación',
                'enabled'                          => 'Habilitado',
                'enabled-hint'                     => 'Interruptor principal de la capa de servicio público. Cuando está desactivado, todas las URL públicas de pasaportes devuelven 404 y el menú de pasaportes se oculta.',
                'base-url'                         => 'URL base',
                'base-url-hint'                    => 'Dirección pública donde se sirven los pasaportes, usada para generar códigos QR y enlaces para compartir. Déjalo en blanco para usar el propio dominio de este sitio.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'TTL de caché (segundos)',
                'cache-ttl-hint'                   => 'Cuánto tiempo se almacena en caché un pasaporte público renderizado antes de reconstruirse. Valores más altos reducen la carga; valores más bajos reflejan los cambios antes.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Límite de solicitudes (peticiones/minuto)',
                'rate-limit-hint'                  => 'Número máximo de solicitudes de pasaportes públicos permitidas por minuto desde un mismo visitante antes de que se le limite.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Permitir la indexación en motores de búsqueda',
                'indexable-hint'                   => 'Permite que los motores de búsqueda indexen las páginas públicas de pasaportes. Desactívalo para que los pasaportes sean accesibles por enlace pero ocultos en los resultados de búsqueda.',
                'gs1-passport-channel'             => 'Canal de pasaporte de GS1 Digital Link',
                'gs1-passport-channel-hint'        => 'El canal al que se resuelve un código de barras GS1 escaneado (/01/{gtin}) cuando un producto se publica en varios canales. Déjalo en blanco para usar el primer canal habilitado.',
                'gs1-passport-channel-placeholder' => 'Primer canal habilitado (automático)',
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
        'product-delete-blocked' => 'Este producto no se puede eliminar mientras tenga pasaportes publicados. Retíralos primero.',
        'channel-delete-blocked' => 'Este canal no se puede eliminar mientras tenga pasaportes publicados. Retíralos primero.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pasaporte no encontrado.',
            'notice'  => 'Este pasaporte de producto no está disponible. Puede que aún no se haya publicado o que el enlace sea incorrecto.',
        ],
        '429' => [
            'heading' => 'Demasiadas solicitudes. Inténtalo de nuevo en unos momentos.',
            'notice'  => 'Has hecho demasiadas solicitudes. Espera un momento e inténtalo de nuevo.',
        ],
        'withdrawn' => [
            'heading' => 'Este pasaporte ya no está disponible.',
            'notice'  => 'Este registro se conserva por transparencia, pero ya no se mantiene de forma activa.',
        ],
    ],
];
