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
            'circular-reference'      => 'Detetada referência circular no modelo: :path.',
            'currency-not-allowed'    => 'Apenas os atributos de preço aceitam uma moeda, mas ":attribute" não é um atributo de preço.',
            'currency-not-enabled'    => 'A moeda ":currency" utilizada no modelo não está ativada para nenhum canal.',
            'currency-not-in-channel' => 'A moeda ":currency" não está ativada para o canal ":channel".',
            'filter-failed'           => 'Não foi possível aplicar o filtro ":filter" ao valor de ":attribute".',
            'filter-not-supported'    => 'O filtro ":filter" não pode ser aplicado ao atributo ":attribute".',
            'format-date-position'    => 'O filtro format_date tem de ser o primeiro filtro em ":expression".',
            'inside-html-tag'         => 'As expressões de modelo não podem ser colocadas dentro de etiquetas ou atributos HTML, como ligações e imagens.',
            'invalid-filter-argument' => 'O filtro ":filter" em ":expression" tem um argumento em falta ou inválido.',
            'invalid-reference'       => 'O modelo guardado em ":attribute" é inválido e não pôde ser processado.',
            'invalid-syntax'          => 'A expressão de modelo ":expression" é inválida. Utilize um código de atributo seguido de filtros opcionais, por exemplo: name | upper.',
            'max-depth'               => 'As referências de modelo estão aninhadas a mais de :max níveis de profundidade em ":attribute".',
            'missing-value'           => 'O atributo ":attribute" não tem valor para este canal e localização.',
            'not-in-family'           => 'O atributo ":attribute" utilizado no modelo não pertence à família do produto.',
            'output-too-large'        => 'O modelo renderizado excede :max bytes e foi truncado.',
            'self-reference'          => 'O modelo não pode fazer referência ao seu próprio atributo ":attribute".',
            'single-brace'            => 'Encontrado ":text" entre chavetas simples. As expressões de modelo necessitam de chavetas duplas.',
            'source-too-long'         => 'Um modelo pode ter, no máximo, :max bytes.',
            'too-complex'             => 'O modelo é demasiado complexo para ser verificado. Encurte-o ou simplifique-o.',
            'too-many-expressions'    => 'Um modelo pode conter, no máximo, :max expressões.',
            'too-many-filters'        => 'A expressão de modelo ":expression" pode utilizar, no máximo, :max filtros.',
            'unclosed-expression'     => 'Uma expressão de modelo foi aberta, mas nunca foi fechada.',
            'unknown-attribute'       => 'O atributo ":attribute" utilizado no modelo não existe.',
            'unknown-currency'        => 'A moeda ":currency" utilizada no modelo não existe.',
            'unknown-filter'          => 'O filtro ":filter" não é suportado. Filtros disponíveis: :filters.',
            'unsupported-attribute'   => 'O atributo ":attribute" não pode ser utilizado num modelo porque o seu tipo não é suportado.',
        ],
    ],
];
