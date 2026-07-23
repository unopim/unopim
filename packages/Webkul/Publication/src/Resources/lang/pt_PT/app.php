<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicação',
            'info'     => 'Camada de disponibilização pública para conteúdo publicado por idioma.',
            'settings' => [
                'title'      => 'Definições de publicação',
                'enabled'    => 'Ativado',
                'base-url'   => 'URL base',
                'cache-ttl'  => 'TTL da cache (segundos)',
                'rate-limit' => 'Limite de taxa (pedidos/minuto)',
                'indexable'  => 'Permitir indexação por motores de pesquisa',
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
            'heading' => 'Demasiados pedidos. Tente novamente dentro de instantes.',
        ],
        'withdrawn' => [
            'heading' => 'Este passaporte já não está disponível.',
        ],
    ],
];
