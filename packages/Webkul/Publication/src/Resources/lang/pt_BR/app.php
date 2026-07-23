<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicação',
            'info'     => 'Camada de disponibilização pública para conteúdo publicado por idioma.',
            'settings' => [
                'title'      => 'Configurações de publicação',
                'enabled'    => 'Ativado',
                'base-url'   => 'URL base',
                'cache-ttl'  => 'TTL do cache (segundos)',
                'rate-limit' => 'Limite de taxa (solicitações/minuto)',
                'indexable'  => 'Permitir indexação por mecanismos de busca',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Rascunho',
            'published' => 'Publicado',
            'withdrawn' => 'Retirado',
            'redacted'  => 'Redigido',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaporte não encontrado.',
        ],
        '429' => [
            'heading' => 'Muitas solicitações. Tente novamente em instantes.',
        ],
        'withdrawn' => [
            'heading' => 'Este passaporte não está mais disponível.',
        ],
    ],
];
