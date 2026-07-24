<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicación',
            'info'     => 'Capa de servicio público para contenido publicado por idioma.',
            'settings' => [
                'title'                  => 'Configuración de publicación',
                'enabled'                => 'Activado',
                'enabled-hint'           => 'Interruptor principal del nivel de servicio público. Cuando está desactivado, cada URL pública de pasaporte devuelve un 404 y el menú de pasaportes se oculta.',
                'base-url'               => 'URL base',
                'base-url-hint'          => 'Dirección pública donde se sirven los pasaportes; se utiliza para generar códigos QR y enlaces para compartir. Déjelo en blanco para usar el dominio propio de este sitio.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl'              => 'TTL de caché (segundos)',
                'cache-ttl-hint'         => 'Cuánto tiempo se almacena en caché un pasaporte público renderizado antes de reconstruirlo. Los valores más altos reducen la carga; los más bajos reflejan los cambios antes.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit'             => 'Límite de velocidad (solicitudes/minuto)',
                'rate-limit-hint'        => 'Número máximo de solicitudes de pasaporte público permitidas por minuto desde un mismo visitante antes de que se le limite.',
                'rate-limit-placeholder' => '60',
                'indexable'              => 'Permitir la indexación por motores de búsqueda',
                'indexable-hint'         => 'Permite que los motores de búsqueda indexen las páginas públicas de pasaporte. Desactívelo para mantener los pasaportes accesibles por enlace pero ocultos en los resultados de búsqueda.',
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
        'product-delete-blocked' => 'Este producto no se puede eliminar mientras tenga pasaportes publicados. Retírelos primero.',
        'channel-delete-blocked' => 'Este canal no se puede eliminar mientras tenga pasaportes publicados. Retírelos primero.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Pasaporte no encontrado.',
            'notice'  => 'Este pasaporte de producto no está disponible. Es posible que aún no se haya publicado o que el enlace sea incorrecto.',
        ],
        '429' => [
            'heading' => 'Demasiadas solicitudes. Inténtelo de nuevo en breve.',
            'notice'  => 'Has realizado demasiadas solicitudes. Espera un momento e inténtalo de nuevo.',
        ],
        'withdrawn' => [
            'heading' => 'Este pasaporte ya no está disponible.',
            'notice'  => 'Este registro se conserva por motivos de transparencia, pero ya no se mantiene activamente.',
        ],
    ],
];
