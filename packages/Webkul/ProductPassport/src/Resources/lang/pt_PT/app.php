<?php

return [
    'type' => [
        'label' => 'Passaporte Digital do Produto',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaporte do Produto',
            'info'     => 'Definições de publicação do passaporte digital do produto.',
            'settings' => [
                'title'                  => 'Definições do passaporte do produto',
                'enabled'                => 'Ativado',
                'auto-publish'           => 'Publicar automaticamente ao guardar',
                'completeness-threshold' => 'Limiar de completude (%)',
                'operator-name'          => 'Nome do operador económico',
                'operator-address'       => 'Morada do operador económico',
                'operator-eu-rep'        => 'Representante autorizado na UE',
                'support-url'            => 'URL de suporte',
            ],
        ],
    ],
];
