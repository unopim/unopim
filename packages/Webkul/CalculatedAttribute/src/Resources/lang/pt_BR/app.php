<?php

return [

    'attribute' => [
        'calculated' => 'Calculado',
    ],

    'formula' => [
        'title'            => 'Fórmula',
        'label'            => 'Fórmula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'O valor é derivado desta fórmula sempre que o produto é apresentado, exportado ou devolvido pela API. Nunca é armazenado e não pode ser importado.',
        'reference-hint'   => 'Referencie outros atributos pelo código deles.',
        'available'        => 'Funções disponíveis',
        'examples'         => 'Exemplos',
        'example-concat'   => 'Juntar dois atributos, ignorando os que estiverem vazios',
        'example-category' => 'O caminho das etiquetas de categoria, unido por #',
        'example-fallback' => 'Recorrer a um segundo atributo quando o primeiro está vazio',
        'read-only'        => 'Calculado',
        'read-only-hint'   => 'Este valor é calculado a partir de outros atributos e não pode ser editado.',

        'variables' => [
            'title'      => 'Variáveis disponíveis',
            'sku'        => 'o SKU do produto',
            'type'       => 'o tipo de produto',
            'status'     => 'o estado do produto',
            'family'     => 'o código da família de atributos',
            'parent'     => 'o SKU do produto pai, para variantes',
            'categories' => 'os códigos das categorias atribuídas',
            'channel'    => 'o canal sendo renderizado',
            'locale'     => 'o idioma sendo renderizado',
        ],

        'errors' => [
            'syntax'              => 'Não foi possível analisar a fórmula: :detail',
            'unknown-variable'    => 'Atributo ou variável ":name" desconhecido na fórmula.',
            'disallowed-function' => 'A função ":name" não é permitida em uma fórmula.',
            'disallowed-operator' => 'O operador ":name" não é permitido em uma fórmula.',
            'reserved-variable'   => 'O nome ":name" é reservado e não pode ser usado em uma fórmula.',
            'too-long'            => 'A fórmula não pode ter mais de :max caracteres.',
            'empty'               => 'Um atributo calculado precisa de uma fórmula.',
            'circular'            => 'A fórmula cria uma dependência circular: :cycle',
            'referenced-by'       => 'Este atributo não pode ser excluído porque é usado pela fórmula de: :attributes',
        ],
    ],

];
