<?php

return [
    'acl' => [
        'variant-structures' => 'Structures de variantes',
    ],
    'datagrid' => [
        'copy-of-slug'                  => 'copia de -:value',
        'copy-of'                       => 'copia de :value',
        'variant-already-exist-message' => 'Ya existe una variante con las mismas opciones de atributos.',
    ],
    'response' => [
        'product-can-not-be-copied' => 'Los productos del tipo :type no se pueden copiar',
    ],
    'type' => [
        'configurable'  => 'Configuré',
        'simple'        => 'Simple',
        'variant-group' => 'Groupe de variantes',
        'variant_group' => 'Groupe de variantes',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Référence circulaire détectée dans le modèle : :path.',
            'currency-not-allowed'    => 'Seuls les attributs de prix acceptent une devise, mais ":attribute" n\'est pas un attribut de prix.',
            'currency-not-enabled'    => 'La devise ":currency" utilisée dans le modèle n\'est activée pour aucun canal.',
            'currency-not-in-channel' => 'La devise ":currency" n\'est pas activée pour le canal ":channel".',
            'filter-failed'           => 'Le filtre ":filter" n\'a pas pu être appliqué à la valeur de ":attribute".',
            'filter-not-supported'    => 'Le filtre ":filter" ne peut pas être appliqué à l\'attribut ":attribute".',
            'format-date-position'    => 'Le filtre format_date doit être le premier filtre dans ":expression".',
            'inside-html-tag'         => 'Les expressions de modèle ne peuvent pas être placées dans des balises ou des attributs HTML, comme les liens et les images.',
            'invalid-filter-argument' => 'Le filtre ":filter" dans ":expression" a un argument manquant ou invalide.',
            'invalid-reference'       => 'Le modèle enregistré dans ":attribute" est invalide et n\'a pas pu être généré.',
            'invalid-syntax'          => 'L\'expression de modèle ":expression" est invalide. Utilisez un code d\'attribut suivi de filtres facultatifs, par exemple : name | upper.',
            'max-depth'               => 'Les références de modèle sont imbriquées sur plus de :max niveaux dans ":attribute".',
            'missing-value'           => 'L\'attribut ":attribute" n\'a pas de valeur pour ce canal et cette langue.',
            'not-in-family'           => 'L\'attribut ":attribute" utilisé dans le modèle n\'appartient pas à la famille du produit.',
            'output-too-large'        => 'Le modèle rendu dépasse :max octets et a été tronqué.',
            'self-reference'          => 'Le modèle ne peut pas faire référence à son propre attribut ":attribute".',
            'single-brace'            => '":text" trouvé entre accolades simples. Les expressions de modèle nécessitent des accolades doubles.',
            'source-too-long'         => 'Un modèle peut contenir au maximum :max octets.',
            'too-complex'             => 'Le modèle est trop complexe pour être vérifié. Raccourcissez-le ou simplifiez-le.',
            'too-many-expressions'    => 'Un modèle peut contenir au maximum :max expressions.',
            'too-many-filters'        => 'L\'expression de modèle ":expression" peut utiliser au maximum :max filtres.',
            'unclosed-expression'     => 'Une expression de modèle a été ouverte mais jamais fermée.',
            'unknown-attribute'       => 'L\'attribut ":attribute" utilisé dans le modèle n\'existe pas.',
            'unknown-currency'        => 'La devise ":currency" utilisée dans le modèle n\'existe pas.',
            'unknown-filter'          => 'Le filtre ":filter" n\'est pas pris en charge. Filtres disponibles : :filters.',
            'unsupported-attribute'   => 'L\'attribut ":attribute" ne peut pas être utilisé dans un modèle, car son type n\'est pas pris en charge.',
        ],
    ],
];
