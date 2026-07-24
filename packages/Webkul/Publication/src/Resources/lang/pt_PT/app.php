<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicação',
            'info'     => 'Camada de disponibilização pública para conteúdo publicado por idioma.',
            'settings' => [
                'title'                  => 'Definições de publicação',
                'enabled'                => 'Ativado',
                'base-url'               => 'URL base',
                'cache-ttl'              => 'TTL da cache (segundos)',
                'rate-limit'             => 'Limite de taxa (pedidos/minuto)',
                'indexable'              => 'Permitir indexação por motores de pesquisa',
                'enabled-hint'           => 'Interruptor principal da camada de disponibilização pública. Quando desativado, todos os URLs de passaporte públicos devolvem 404 e o menu de passaportes fica oculto.',
                'base-url-hint'          => 'Endereço público onde os passaportes são disponibilizados, utilizado para criar códigos QR e ligações partilháveis. Deixe em branco para utilizar o próprio domínio deste site.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl-hint'         => 'Durante quanto tempo um passaporte público renderizado fica em cache antes de ser reconstruído. Valores mais altos reduzem a carga; valores mais baixos refletem as edições mais depressa.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit-hint'        => 'Número máximo de pedidos de passaporte público permitidos por minuto a partir de um único visitante antes de ser limitado.',
                'rate-limit-placeholder' => '60',
                'indexable-hint'         => 'Permita que os motores de busca indexem as páginas de passaporte públicas. Desative para manter os passaportes acessíveis por ligação, mas ocultos dos resultados de pesquisa.',
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
        'product-delete-blocked' => 'Este produto não pode ser eliminado enquanto tiver passaportes publicados. Retire-os primeiro.',
        'channel-delete-blocked' => 'Este canal não pode ser eliminado enquanto tiver passaportes publicados. Retire-os primeiro.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passaporte não encontrado.',
            'notice'  => 'Este passaporte de produto não está disponível. Poderá ainda não ter sido publicado ou a ligação poderá estar incorreta.',
        ],
        '429' => [
            'heading' => 'Demasiados pedidos. Tente novamente dentro de instantes.',
            'notice'  => 'Efetuou demasiados pedidos. Aguarde um momento e tente novamente.',
        ],
        'withdrawn' => [
            'heading' => 'Este passaporte já não está disponível.',
            'notice'  => 'Este registo é mantido para efeitos de transparência, mas já não é atualizado ativamente.',
        ],
    ],
];
