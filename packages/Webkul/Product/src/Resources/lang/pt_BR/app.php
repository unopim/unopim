<?php

return [
    'acl' => [
        'variant-structures' => 'Estruturas de variantes',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'cópia-de-:value',
        'copy-of'                       => 'Cópia de :value',
        'variant-already-exist-message' => 'Variante com as mesmas opções de atributo já existe.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produtos do tipo :type não podem ser copiados',
    ],

    'type'     => [
        'configurable'  => 'Configurável',
        'simple'        => 'Simples',
        'variant-group' => 'Grupo de variantes',
        'variant_group' => 'Grupo de variantes',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Referência circular de modelo detectada: :path.',
            'currency-not-allowed'    => 'Somente atributos de preço aceitam uma moeda, mas ":attribute" não é um atributo de preço.',
            'currency-not-enabled'    => 'A moeda ":currency" usada no modelo não está habilitada para nenhum canal.',
            'currency-not-in-channel' => 'A moeda ":currency" não está habilitada para o canal ":channel".',
            'filter-failed'           => 'Não foi possível aplicar o filtro ":filter" ao valor de ":attribute".',
            'filter-not-supported'    => 'O filtro ":filter" não pode ser aplicado ao atributo ":attribute".',
            'format-date-position'    => 'O filtro format_date deve ser o primeiro filtro em ":expression".',
            'inside-html-tag'         => 'Expressões de modelo não podem ser colocadas dentro de tags ou atributos HTML, como links e imagens.',
            'invalid-filter-argument' => 'O filtro ":filter" em ":expression" tem um argumento ausente ou inválido.',
            'invalid-reference'       => 'O modelo armazenado em ":attribute" é inválido e não pôde ser renderizado.',
            'invalid-syntax'          => 'A expressão de modelo ":expression" é inválida. Use um código de atributo seguido de filtros opcionais, por exemplo: name | upper.',
            'max-depth'               => 'As referências de modelo estão aninhadas em mais de :max níveis de profundidade em ":attribute".',
            'missing-value'           => 'O atributo ":attribute" não tem valor para este canal e localidade.',
            'not-in-family'           => 'O atributo ":attribute" usado no modelo não pertence à família do produto.',
            'output-too-large'        => 'O modelo renderizado excede :max bytes e foi truncado.',
            'self-reference'          => 'O modelo não pode referenciar seu próprio atributo ":attribute".',
            'single-brace'            => '":text" encontrado entre chaves simples. Expressões de modelo precisam de chaves duplas.',
            'source-too-long'         => 'Um modelo pode ter no máximo :max bytes.',
            'too-complex'             => 'O modelo é complexo demais para ser verificado. Encurte-o ou simplifique-o.',
            'too-many-expressions'    => 'Um modelo pode conter no máximo :max expressões.',
            'too-many-filters'        => 'A expressão de modelo ":expression" pode usar no máximo :max filtros.',
            'unclosed-expression'     => 'Uma expressão de modelo foi aberta, mas nunca foi fechada.',
            'unknown-attribute'       => 'O atributo ":attribute" usado no modelo não existe.',
            'unknown-currency'        => 'A moeda ":currency" usada no modelo não existe.',
            'unknown-filter'          => 'O filtro ":filter" não é suportado. Filtros disponíveis: :filters.',
            'unsupported-attribute'   => 'O atributo ":attribute" não pode ser usado em um modelo porque seu tipo não é suportado.',
        ],
    ],
];
