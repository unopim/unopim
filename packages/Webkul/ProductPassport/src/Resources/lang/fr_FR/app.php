<?php

return [
    'type' => [
        'label' => 'Passeport Numérique du Produit',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Passeport du Produit',
            'info'     => 'Paramètres de publication du passeport numérique du produit.',
            'settings' => [
                'title'                  => 'Paramètres du passeport produit',
                'enabled'                => 'Activé',
                'auto-publish'           => 'Publier automatiquement lors de l\'enregistrement',
                'completeness-threshold' => 'Seuil de complétude (%)',
                'operator-name'          => 'Nom de l\'opérateur économique',
                'operator-address'       => 'Adresse de l\'opérateur économique',
                'operator-eu-rep'        => 'Représentant autorisé dans l\'UE',
                'support-url'            => 'URL d\'assistance',
            ],
        ],
    ],
];
