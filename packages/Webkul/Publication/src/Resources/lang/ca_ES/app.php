<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicació',
            'info'     => 'Nivell de servei públic per a contingut publicat, per idioma.',
            'settings' => [
                'title'      => 'Configuració de publicació',
                'enabled'    => 'Activat',
                'base-url'   => 'URL base',
                'cache-ttl'  => 'TTL de la memòria cau (segons)',
                'rate-limit' => 'Límit de velocitat (sol·licituds/minut)',
                'indexable'  => 'Permet la indexació per motors de cerca',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Esborrany',
            'published' => 'Publicat',
            'withdrawn' => 'Retirat',
            'redacted'  => 'Censurat',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaport no trobat.',
        ],
        '429' => [
            'heading' => 'Massa sol·licituds. Torneu-ho a provar d\'aquí a poc.',
        ],
        'withdrawn' => [
            'heading' => 'Aquest passaport ja no està disponible.',
        ],
    ],
];
