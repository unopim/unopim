<?php

return [

    'attribute' => [
        'calculated' => 'Calculé',
    ],

    'formula' => [
        'title'            => 'Formule',
        'label'            => 'Formule',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => "La valeur est dérivée de cette formule à chaque affichage du produit, à chaque export et à chaque appel de l'API. Elle n'est jamais stockée et ne peut pas être importée.",
        'reference-hint'   => 'Référencez les autres attributs par leur code.',
        'available'        => 'Fonctions disponibles',
        'examples'         => 'Exemples',
        'example-concat'   => 'Joindre deux attributs en ignorant ceux qui sont vides',
        'example-category' => 'Le chemin des libellés de catégorie, joint par #',
        'example-fallback' => 'Utiliser un second attribut lorsque le premier est vide',
        'read-only'        => 'Calculé',
        'read-only-hint'   => "Cette valeur est calculée à partir d'autres attributs et ne peut pas être modifiée.",

        'variables' => [
            'title'      => 'Variables disponibles',
            'sku'        => 'la référence du produit',
            'type'       => 'le type de produit',
            'status'     => 'le statut du produit',
            'family'     => "le code de la famille d'attributs",
            'parent'     => 'la référence du produit parent, pour les variantes',
            'categories' => 'les codes des catégories affectées',
            'channel'    => 'le canal en cours de rendu',
            'locale'     => 'la langue en cours de rendu',
        ],

        'errors' => [
            'syntax'              => "La formule n'a pas pu être analysée : :detail",
            'unknown-variable'    => 'Attribut ou variable « :name » inconnu dans la formule.',
            'disallowed-function' => "La fonction « :name » n'est pas autorisée dans une formule.",
            'disallowed-operator' => "L'opérateur « :name » n'est pas autorisé dans une formule.",
            'reserved-variable'   => 'Le nom « :name » est réservé et ne peut pas être utilisé dans une formule.',
            'too-long'            => 'La formule ne peut pas dépasser :max caractères.',
            'empty'               => 'Un attribut calculé nécessite une formule.',
            'circular'            => 'La formule crée une dépendance circulaire : :cycle',
            'referenced-by'       => 'Cet attribut ne peut pas être supprimé car il est utilisé par la formule de : :attributes',
        ],
    ],

];
