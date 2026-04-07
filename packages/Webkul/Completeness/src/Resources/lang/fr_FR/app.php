<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Complétude',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Complétude mise à jour avec succès',
                    'title'               => 'Complétude',
                    'configure'           => 'Configurer la complétude',
                    'channel-required'    => 'Requis dans les canaux',
                    'save-btn'            => 'Enregistrer',
                    'back-btn'            => 'Retour',
                    'mass-update-success' => 'Complétude mise à jour avec succès',
                    'datagrid'            => [
                        'code'             => 'Code',
                        'name'             => 'Nom',
                        'channel-required' => 'Requis dans les canaux',
                        'actions'          => [
                            'change-requirement' => 'Modifier l\'exigence de complétude',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Complet',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Complétude',
                    'subtitle' => 'Complétude moyenne',
                ],
                'required-attributes' => 'attributs requis manquants',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Calcul de complétude terminé',
        'completeness-calculated'        => 'Complétude calculée pour :count produits.',
        'completeness-calculated-family' => 'Complétude calculée pour :count produits dans la famille ":family".',
        'email-subject'                  => 'Calcul de complétude terminé',
        'email-greeting'                 => 'Bonjour,',
        'email-body'                     => 'Le calcul de complétude a été réalisé pour :count produits.',
        'email-body-family'              => 'Le calcul de complétude a été réalisé pour :count produits dans la famille d\'attributs ":family".',
        'email-footer'                   => 'Vous pouvez consulter les détails de complétude sur votre tableau de bord.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Produits calculés',
                'suggestion'          => [
                    'low'     => 'Complétude faible, ajoutez des détails pour améliorer.',
                    'medium'  => 'Continuez, poursuivez l\'ajout d\'informations.',
                    'high'    => 'Presque complet, il ne reste que quelques détails.',
                    'perfect' => 'Les informations du produit sont entièrement complètes.',
                ],
            ],
        ],
    ],
];
