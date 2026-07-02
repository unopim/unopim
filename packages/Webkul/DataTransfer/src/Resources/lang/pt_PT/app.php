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
                    'code_not_found_to_delete' => 'Código do campo de categoria não encontrado para eliminação.',
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
        'locales' => [
            'title'      => 'Idiomas',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'O código de idioma \'%s\' já foi importado neste lote.',
                    'code-not-found-to-delete'    => 'Idioma com código \'%s\' não encontrado no sistema.',
                    'invalid-status'              => 'O estado deve ser 0 ou 1 (ou vazio para ativado por defeito).',
                    'channel-related-locale-root' => 'Não pode eliminar o idioma com código :code porque está associado a um canal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canais',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Canal com o código :code não encontrado para eliminar.',
                    'locale-not-found'         => 'Um ou mais idiomas não existem.',
                    'root-category-not-found'  => 'A categoria raiz não existe.',
                    'currency-not-found'       => 'Uma ou mais moedas não existem.',
                    'invalid-locale'           => 'O idioma não existe.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Currencies',
            'filters' => [
                'status' => 'Estado',
                'enable' => 'Ativar',
                'all'    => 'Todos',
            ],
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
            'title'   => 'Users',
            'filters' => [
                'status' => 'Estado',
                'active' => 'Ativo',
                'all'    => 'Todos',
            ],
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
        'export-too-large' => 'Esta exportação é demasiado grande para ser executada: cerca de :rows linhas × :columns colunas (~:estimated) excedem o espaço disponível (~:available). Reduza a exportação selecionando menos canais/locais (e atributos) e tente novamente.',
        'fields'           => [
            'file-format'         => 'Formato de ficheiro',
            'with-media'          => 'Com multimédia',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Estado',
            'enable'         => 'Ativado',
            'all'            => 'Todos',
        ],
        'products' => [
            'title'              => 'Produtos',
            'invalid-locales'    => 'Nem todos os idiomas selecionados estão disponíveis para os canais selecionados.',
            'invalid-currencies' => 'Nem todas as moedas selecionadas estão disponíveis para os canais selecionados.',
            'filters'            => [
                'channels'             => 'Canais',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Moedas',
                'currencies-info'      => 'Os atributos de preço são exportados por moeda selecionada. Deixe em branco para exportar todas as moedas do canal.',
                'locales'              => 'Idiomas',
                'locales-info'         => 'Os atributos localizáveis são exportados uma vez por idioma selecionado. Deixe em branco para exportar todos os idiomas do canal.',
                'attributes'           => 'Atributos',
                'attributes-info'      => 'Apenas os atributos selecionados são exportados. Deixe em branco para exportar todos os atributos da família.',
                'attribute-families'   => 'Famílias de atributos',
                'categories'           => 'Categorias',
                'completeness'         => 'Completude',
                'completeness-options' => [
                    'none'         => 'Sem condição de completude',
                    'at-least-one' => 'Completo em pelo menos um idioma selecionado',
                    'all'          => 'Completo em todos os idiomas selecionados',
                ],
                'time-condition' => 'Condição de tempo',
                'time-options'   => [
                    'none'              => 'Sem condição de data',
                    'last-n-days'       => 'Produtos atualizados nos últimos N dias',
                    'between-dates'     => 'Produtos atualizados entre duas datas',
                    'since-last-export' => 'Produtos atualizados desde a última exportação',
                ],
                'time-value'     => 'Número de dias',
                'time-date'      => 'Data de início',
                'time-date-end'  => 'Data de fim',
                'status'         => 'Estado',
                'status-options' => [
                    'enable'  => 'Ativado',
                    'disable' => 'Desativado',
                    'all'     => 'Todos',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificadores',
                'identifiers-info' => 'Cole um SKU / identificador por linha para exportar apenas esses produtos. Deixe em branco para exportar todos os produtos.',
            ],
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
        'locales' => [
            'title' => 'Idiomas',
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
                'status' => 'Estado',
                'active' => 'Active',
                'all'    => 'Todos',
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
            'file-empty'           => 'O ficheiro está vazio ou não contém uma linha de cabeçalho. Por favor, carregue um ficheiro válido com dados.',
        ],
    ],
    'job' => [
        'started'   => 'Início da execução do trabalho',
        'completed' => 'Conclusão da execução do trabalho',
    ],
];
