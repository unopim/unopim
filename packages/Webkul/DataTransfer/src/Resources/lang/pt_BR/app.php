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
                    'super-attribute-not-found'                => 'Atributo configurável com código: \'%s\' não encontrado ou não pertence à família de atributos: \'%s\' :code :familyCode',
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
        'category-fields' => [
            'title'      => 'Campos de categoria',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'O código do campo de categoria :code já está em uso.',
                    'code_not_found_to_delete' => 'Código do campo de categoria não encontrado para exclusão.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'O código do atributo :code já está em uso.',
                    'code_not_found_to_delete'             => 'Código de atributo não encontrado para exclusão.',
                    'code_is_system_and_cannot_be_deleted' => 'O atributo do sistema não pode ser excluído.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grupos de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'O código do grupo de atributos :code já está em uso.',
                    'code_not_found_to_delete'             => 'Código de grupo de atributos não encontrado para exclusão.',
                    'code_is_system_and_cannot_be_deleted' => 'O grupo de atributos do sistema não pode ser excluído.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Famílias de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'O código da família de atributos :code já está em uso.',
                    'code_not_found_to_delete' => 'Código de família de atributos não encontrado para exclusão.',
                    'invalid-attribute-group'  => 'O grupo de atributos ":code" não existe.',
                    'invalid-attribute'        => 'O atributo ":code" não existe.',
                    'invalid-channel'          => 'O canal ":code" não existe.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opções de atributos',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'O código de opção de atributo :code já está em uso.',
                    'code_not_found_to_delete' => 'Código de opção de atributo não encontrado para exclusão.',
                    'locale-not-exist'         => 'A localidade ":code" não existe.',
                    'invalid-attribute'        => 'O atributo ":code" não existe.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canais',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Canal com código :code não encontrado para exclusão.',
                    'locale-not-found'         => 'Um ou mais idiomas não existem.',
                    'root-category-not-found'  => 'A categoria raiz não existe.',
                    'currency-not-found'       => 'Uma ou mais moedas não existem.',
                    'invalid-locale'           => 'O idioma não existe.',
                ],
            ],
        ],
        'currencies' => [
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
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
        'category-fields' => [
            'title' => 'Campos de categoria',
        ],
        'attributes' => [
            'title' => 'Atributos',
        ],
        'attribute-groups' => [
            'title' => 'Grupos de atributos',
        ],
        'attribute-families' => [
            'title' => 'Famílies de atributos',
        ],
        'attribute-options' => [
            'title' => 'Opções de atributos',
        ],
        'channels' => [
            'title' => 'Canais',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Início da execução do trabalho',
        'completed' => 'Conclusão da execução do trabalho',
    ],
];
