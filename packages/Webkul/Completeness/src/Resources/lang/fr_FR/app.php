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

    'configuration' => [
        'score-thresholds' => [
            'title'           => 'Couleurs du score de complétude',
            'info'            => 'Définissez les scores à partir desquels les indicateurs de complétude passent au vert ou au rouge.',
            'green-from'      => 'Vert à partir de (%)',
            'green-from-info' => 'Les scores supérieurs ou égaux à cette valeur s\'affichent en vert.',
            'red-below'       => 'Rouge en dessous de (%)',
            'red-below-info'  => 'Les scores inférieurs à cette valeur s\'affichent en rouge. Les scores intermédiaires s\'affichent en orange.',
            'order-error'     => 'Le seuil du vert doit être supérieur au seuil du rouge.',
        ],
    ],
];
