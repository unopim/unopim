<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produtos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Chave URL: \'%s\' já foi gerada para um item com SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valor inválido para a coluna da família de atributos (a família de atributos não existe?)',
                    'invalid-type'                             => 'Tipo de produto inválido ou não suportado',
                    'sku-not-found'                            => 'Produto com SKU especificado não encontrado',
                    'super-attribute-not-found'                => 'Atributo configurável com código: \'%s\' não encontrado ou não pertence à família de atributos: \'%s\'',
                    'configurable-attributes-not-found'        => 'Atributos configuráveis são necessários para criar modelo de produto',
                    'configurable-attributes-wrong-type'       => 'Apenas atributos do tipo que não são baseados em local ou canal podem ser atributos configuráveis para um produto configurável',
                    'variant-configurable-attribute-not-found' => 'Atributo configurável variante: :code é necessário para criar',
                    'not-unique-variant-product'               => 'Um produto com mesmos atributos configuráveis já existe.',
                    'channel-not-exist'                        => 'Este canal não existe.',
                    'locale-not-in-channel'                    => 'Este local não está selecionado no canal.',
                    'locale-not-exist'                         => 'Este local não existe',
                    'not-unique-value'                         => 'O valor :code deve ser único.',
                    'incorrect-family-for-variant'             => 'A família deve ser a mesma que a família principal',
                    'parent-not-exist'                         => 'O pai não existe.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorias',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Você não pode excluir a categoria raiz associada a um canal',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Produtos',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Chave URL: \'%s\' já foi gerada para um item com SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valor inválido para a coluna da família de atributos (a família de atributos não existe?)',
                    'invalid-type'              => 'Tipo de produto inválido ou não suportado',
                    'sku-not-found'             => 'Produto com SKU especificado não encontrado',
                    'super-attribute-not-found' => 'Atributo configurável com código: \'%s\' não encontrado ou não pertence à família de atributos: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorias',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'As colunas número "%s" têm cabeçalhos vazios.',
            'column-name-invalid'  => 'Cabeçalhos de coluna inválidos: "%s".',
            'column-not-found'     => 'Colunas necessárias não encontradas: %s.',
            'column-numbers'       => 'O número de colunas não corresponde ao número de linhas no cabeçalho.',
            'invalid-attribute'    => 'O cabeçalho contém atributos inválidos: "%s".',
            'system'               => 'Ocorreu um erro do sistema inesperado.',
            'wrong-quotes'         => 'Aspas inclinadas usadas em vez de aspas diretas.',
        ],
    ],
    'job' => [
        'started'   => 'Início da execução do trabalho',
        'completed' => 'Conclusão da execução do trabalho',
    ],
];
