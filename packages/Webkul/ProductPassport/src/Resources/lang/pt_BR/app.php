<?php

return [
    'type' => [
        'label' => 'Passaporte Digital do Produto',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaporte do Produto',
            'info'     => 'Configurações de publicação do passaporte digital do produto.',
            'settings' => [
                'title'                  => 'Configurações do passaporte do produto',
                'enabled'                => 'Ativado',
                'auto-publish'           => 'Publicar automaticamente ao salvar',
                'completeness-threshold' => 'Limite de completude (%)',
                'operator-name'          => 'Nome do operador econômico',
                'operator-address'       => 'Endereço do operador econômico',
                'operator-eu-rep'        => 'Representante autorizado na UE',
                'support-url'            => 'URL de suporte',
            ],
        ],
    ],
];
