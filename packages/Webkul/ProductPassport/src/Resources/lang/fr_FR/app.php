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
                'title'                              => 'Paramètres du passeport produit',
                'enabled'                            => 'Activé',
                'enabled-hint'                       => 'Activer la fonctionnalité de Passeport Numérique de Produit pour ce catalogue. Lorsqu\'elle est désactivée, le panneau et la grille des passeports sont masqués.',
                'auto-publish'                       => 'Publier automatiquement lors de l\'enregistrement',
                'auto-publish-hint'                  => 'Publier automatiquement une version du passeport chaque fois qu\'un produit est enregistré et atteint le seuil de complétude. Laissez désactivé pour publier manuellement.',
                'completeness-threshold'             => 'Seuil de complétude (%)',
                'completeness-threshold-hint'        => 'Complétude minimale du produit, en pourcentage, requise avant qu\'un passeport puisse être publié pour une langue.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Nom de l\'opérateur économique',
                'operator-name-hint'                 => 'Raison sociale du fabricant ou de l\'opérateur économique responsable, affichée sur chaque passeport public conformément au règlement ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Adresse de l\'opérateur économique',
                'operator-address-hint'              => 'Adresse postale enregistrée de l\'opérateur économique, affichée sur le passeport public à des fins de traçabilité.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Représentant autorisé dans l\'UE',
                'operator-eu-rep-hint'               => 'Nom et coordonnées du représentant autorisé dans l\'UE, requis lorsque le fabricant est établi en dehors de l\'UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL d\'assistance',
                'support-url-hint'                   => 'Page publique où les clients peuvent trouver de l\'aide ou des informations de garantie. Affichée sous forme de lien sur chaque passeport.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Passeport Numérique du Produit',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Composition des matériaux',
        'dpp_substances_of_concern'     => 'Substances préoccupantes',
        'dpp_recycled_content_pct'      => 'Contenu recyclé (%)',
        'dpp_carbon_footprint'          => 'Empreinte carbone',
        'dpp_energy_consumption'        => 'Consommation d\'énergie',
        'dpp_durability_statement'      => 'Déclaration de durabilité',
        'dpp_repairability_score'       => 'Indice de réparabilité',
        'dpp_spare_parts_availability'  => 'Disponibilité des pièces détachées',
        'dpp_care_instructions'         => 'Instructions d\'entretien',
        'dpp_disassembly_guide'         => 'Guide de démontage',
        'dpp_manufacturer_name'         => 'Nom du fabricant',
        'dpp_manufacturing_site'        => 'Site de fabrication',
        'dpp_country_of_origin'         => 'Pays d\'origine',
        'dpp_supply_chain_notes'        => 'Notes sur la chaîne d\'approvisionnement',
        'dpp_end_of_life_instructions'  => 'Instructions de fin de vie',
        'dpp_take_back_scheme'          => 'Programme de reprise',
        'dpp_declaration_of_conformity' => 'Déclaration de conformité',
        'dpp_test_reports'              => 'Rapports d\'essais',
        'dpp_certificates'              => 'Certificats',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identifiant du modèle',
        'dpp_batch_identifier'          => 'Identifiant du lot',
        'dpp_warranty_terms'            => 'Conditions de garantie',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Les attributs du passeport numérique du produit ont été installés avec succès.',
        ],
    ],
    'public' => [
        'title'         => 'Passeport Numérique du Produit',
        'badge'         => 'Passeport numérique de produit UE',
        'search-locale' => 'Rechercher une langue',
        'sections'      => [
            'passport' => 'Passeport du Produit',
        ],
        'identifier' => [
            'title'        => 'Identification',
            'gtin'         => 'GTIN',
            'model'        => 'Modèle',
            'batch'        => 'Lot',
            'not-provided' => 'Non fourni',
        ],
        'operator' => [
            'title' => 'Opérateur économique',
        ],
        'documents' => [
            'title' => 'Documents',
        ],
    ],
    'publications' => [
        'index' => [
            'title'           => 'Passeports Numériques du Produit',
            'disabled-notice' => 'La publication des passeports est actuellement désactivée. Les passeports existants sont affichés ci-dessous pour gestion (consultation et retrait).',
        ],
        'datagrid' => [
            'uuid'           => 'UUID',
            'sku'            => 'SKU',
            'channel'        => 'Canal',
            'status'         => 'Statut',
            'live-locales'   => 'Langues actives',
            'last-published' => 'Dernière publication',
            'withdraw'       => 'Retirer',
        ],
        'publish-queued' => 'La publication du passeport a été mise en file d\'attente.',
        'withdrawn'      => 'Passeport retiré avec succès.',
        'mass-publish'   => [
            'action' => 'Publier le passeport numérique de produit',
            'queued' => 'Publication du passeport mise en file d\'attente pour :count produit(s).',
        ],
    ],
    'acl' => [
        'passports' => [
            'index'    => 'Passeports',
            'view'     => 'Voir',
            'publish'  => 'Publier',
            'withdraw' => 'Retirer',
        ],
    ],
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passeports',
                    ],
                ],
            ],
        ],
    ],
    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'title'               => 'Passeport Numérique du Produit',
                    'publishing-disabled' => 'La publication des passeports est désactivée pour ce canal.',
                    'locale'              => 'Langue',
                    'version'             => 'Version',
                    'published-at'        => 'Publié le',
                    'missing-fields'      => 'Champs manquants',
                    'not-published'       => 'Non publié',
                    'unscored'            => 'Non évalué',
                    'publish'             => 'Publier',
                    'republish'           => 'Republier',
                    'publish-all'         => 'Publier toutes les langues',
                    'auto-publish-on'     => 'La publication automatique est activée — les passeports sont publiés automatiquement lorsque le produit est enregistré et atteint le seuil de complétude. Utilisez les boutons pour publier maintenant.',
                    'auto-publish-off'    => 'Publication manuelle — utilisez les boutons pour publier le passeport de ce produit pour chaque langue.',
                    'publishing'          => 'Publication…',
                    'queued'              => 'En file d\'attente',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Le champ :attribute doit être un GTIN valide (8, 12, 13 ou 14 chiffres avec une clé de contrôle correcte).',
    ],
];
