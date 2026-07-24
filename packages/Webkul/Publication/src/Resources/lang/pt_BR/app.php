<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicação',
            'info'     => 'Camada de disponibilização pública para conteúdo publicado por idioma.',
            'settings' => [
                'title'                  => 'Configurações de publicação',
                'enabled'                => 'Ativado',
                'base-url'               => 'URL base',
                'cache-ttl'              => 'TTL do cache (segundos)',
                'rate-limit'             => 'Limite de taxa (solicitações/minuto)',
                'indexable'              => 'Permitir indexação por mecanismos de busca',
                'enabled-hint'           => 'Interruptor principal da camada de exibição pública. Quando desativado, todas as URLs de passaporte públicas retornam 404 e o menu de passaportes fica oculto.',
                'base-url-hint'          => 'Endereço público onde os passaportes são servidos, usado para criar códigos QR e links compartilháveis. Deixe em branco para usar o próprio domínio deste site.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl-hint'         => 'Por quanto tempo um passaporte público renderizado fica em cache antes de ser reconstruído. Valores mais altos reduzem a carga; valores mais baixos refletem edições mais rápido.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit-hint'        => 'Número máximo de solicitações de passaporte público permitidas por minuto de um único visitante antes de ser limitado.',
                'rate-limit-placeholder' => '60',
                'indexable-hint'         => 'Permita que mecanismos de busca indexem as páginas de passaporte públicas. Desative para manter os passaportes acessíveis por link, mas ocultos dos resultados de busca.',
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
        'product-delete-blocked' => 'Este produto não pode ser excluído enquanto tiver passaportes publicados. Retire-os primeiro.',
        'channel-delete-blocked' => 'Este canal não pode ser excluído enquanto tiver passaportes publicados. Retire-os primeiro.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaporte não encontrado.',
            'notice'  => 'Este passaporte de produto não está disponível. Talvez ainda não tenha sido publicado ou o link esteja incorreto.',
        ],
        '429' => [
            'heading' => 'Muitas solicitações. Tente novamente em instantes.',
            'notice'  => 'Você fez muitas solicitações. Aguarde um momento e tente novamente.',
        ],
        'withdrawn' => [
            'heading' => 'Este passaporte não está mais disponível.',
            'notice'  => 'Este registro é mantido para fins de transparência, mas não é mais atualizado ativamente.',
        ],
    ],
];
