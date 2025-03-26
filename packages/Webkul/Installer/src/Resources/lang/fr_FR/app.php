<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Défaut',
            ],

            'attribute-groups' => [
                'description'      => 'Description',
                'general'          => 'Général',
                'inventories'      => 'Inventaires',
                'meta-description' => 'Méta-description',
                'price'            => 'Prix',
                'technical'        => 'Technique',
                'shipping'         => 'Expédition',
            ],

            'attributes' => [
                'brand'                => 'Marque',
                'color'                => 'Couleur',
                'cost'                 => 'Coût',
                'description'          => 'Description',
                'featured'             => 'En vedette',
                'guest-checkout'       => 'Paiement des invités',
                'height'               => 'Hauteur',
                'length'               => 'Longueur',
                'manage-stock'         => 'Gérer les stocks',
                'meta-description'     => 'Méta-description',
                'meta-keywords'        => 'Méta-mots-clés',
                'meta-title'           => 'Méta-titre',
                'name'                 => 'Nom',
                'new'                  => 'Nouveau',
                'price'                => 'Prix',
                'product-number'       => 'Numéro de produit',
                'short-description'    => 'Brève description',
                'size'                 => 'Taille',
                'sku'                  => 'UGS',
                'special-price-from'   => 'Prix ​​spécial à partir de',
                'special-price-to'     => 'Prix ​​spécial à',
                'special-price'        => 'Prix ​​spécial',
                'status'               => 'Statut',
                'tax-category'         => 'Catégorie de taxe',
                'url-key'              => 'Clé URL',
                'visible-individually' => 'Visible individuellement',
                'weight'               => 'Poids',
                'width'                => 'Largeur',
            ],

            'attribute-options' => [
                'black'  => 'Noir',
                'green'  => 'Vert',
                'l'      => 'L',
                'm'      => 'M.',
                'red'    => 'Rouge',
                's'      => 'S',
                'white'  => 'Blanc',
                'xl'     => 'XL',
                'yellow' => 'Jaune',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'Description de la catégorie racine',
                'name'        => 'Racine',
            ],

            'category_fields' => [
                'name'        => 'Nom',
                'description' => 'Description',
            ],
        ],

        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Contenu de la page À propos de nous',
                    'title'   => 'À propos de nous',
                ],

                'contact-us' => [
                    'content' => 'Contenu de la page Contactez-nous',
                    'title'   => 'Contactez-nous',
                ],

                'customer-service' => [
                    'content' => 'Contenu de la page du service client',
                    'title'   => 'Service client',
                ],

                'payment-policy' => [
                    'content' => 'Contenu de la page Politique de paiement',
                    'title'   => 'Politique de paiement',
                ],

                'privacy-policy' => [
                    'content' => 'Contenu de la page Politique de confidentialité',
                    'title'   => 'politique de confidentialité',
                ],

                'refund-policy' => [
                    'content' => 'Contenu de la page Politique de remboursement',
                    'title'   => 'Politique de remboursement',
                ],

                'return-policy' => [
                    'content' => 'Contenu de la page Politique de retour',
                    'title'   => 'Politique de retour',
                ],

                'shipping-policy' => [
                    'content' => 'Contenu de la page Politique d\’expédition',
                    'title'   => 'Politique d\'expédition',
                ],

                'terms-conditions' => [
                    'content' => 'Contenu de la page Conditions générales',
                    'title'   => 'Conditions générales',
                ],

                'terms-of-use' => [
                    'content' => 'Contenu de la page Conditions d\'utilisation',
                    'title'   => 'Conditions d\'utilisation',
                ],

                'whats-new' => [
                    'content' => 'Contenu de la page Quoi de neuf',
                    'title'   => 'Quoi de neuf',
                ],
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'Boutique de démonstration',
                'meta-keywords'    => 'Mot-clé méta du magasin de démonstration',
                'meta-description' => 'Méta description de la boutique de démonstration',
                'name'             => 'Défaut',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Shekel israélien',
                'CNY' => 'Yuan chinois',
                'EUR' => 'EURO',
                'GBP' => 'Livre sterling',
                'INR' => 'Roupie indienne',
                'IRR' => 'Rial iranien',
                'JPY' => 'Yens japonais',
                'RUB' => 'Rouble russe',
                'SAR' => 'Riyal saoudien',
                'TRY' => 'Lire turque',
                'UAH' => 'Hryvnia ukrainienne',
                'USD' => 'Dollar américain',
            ],
        ],

        'customer' => [
            'customer-groups' => [
                'general'   => 'Général',
                'guest'     => 'Invité',
                'wholesale' => 'De gros',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Défaut',
            ],
        ],

        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name' => 'Tous les produits',

                    'options' => [
                        'title' => 'Tous les produits',
                    ],
                ],

                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Tout afficher',
                        'description' => 'Présentation de nos nouvelles collections audacieuses ! Élevez votre style avec des designs audacieux et des déclarations vibrantes. Explorez des motifs saisissants et des couleurs vives qui redéfinissent votre garde-robe. Préparez-vous à embrasser l\'extraordinaire !',
                        'title'       => 'Préparez-vous pour nos nouvelles collections audacieuses !',
                    ],

                    'name' => 'Collections audacieuses',
                ],

                'categories-collections' => [
                    'name' => 'Catégories Collections',
                ],

                'featured-collections' => [
                    'name' => 'Collections en vedette',

                    'options' => [
                        'title' => 'Produits en vedette',
                    ],
                ],

                'footer-links' => [
                    'name' => 'Liens de pied de page',

                    'options' => [
                        'about-us'         => 'À propos de nous',
                        'contact-us'       => 'Contactez-nous',
                        'customer-service' => 'Service client',
                        'payment-policy'   => 'Politique de paiement',
                        'privacy-policy'   => 'politique de confidentialité',
                        'refund-policy'    => 'Politique de remboursement',
                        'return-policy'    => 'Politique de retour',
                        'shipping-policy'  => 'Politique d\'expédition',
                        'terms-conditions' => 'Conditions générales',
                        'terms-of-use'     => 'Conditions d\'utilisation',
                        'whats-new'        => 'Quoi de neuf',
                    ],
                ],

                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Nos collections',
                        'sub-title-2' => 'Nos collections',
                        'title'       => 'Le jeu avec nos nouveaux ajouts!',
                    ],

                    'name' => 'Conteneur de jeu',
                ],

                'image-carousel' => [
                    'name' => 'Carrousel d’images',

                    'sliders' => [
                        'title' => 'Préparez-vous pour la nouvelle collection',
                    ],
                ],

                'new-products' => [
                    'name' => 'Nouveaux produits',

                    'options' => [
                        'title' => 'Nouveaux produits',
                    ],
                ],

                'offer-information' => [
                    'content' => [
                        'title' => 'Obtenez JUSQU\'À 40 % DE RÉDUCTION sur votre 1ère commande ACHETEZ MAINTENANT',
                    ],

                    'name' => 'Informations sur l\'offre',
                ],

                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'EMI sans frais disponible sur toutes les principales cartes de crédit',
                        'free-shipping-info'   => 'Bénéficiez de la livraison gratuite sur toutes les commandes',
                        'product-replace-info' => 'Remplacement facile du produit disponible !',
                        'time-support-info'    => 'Assistance dédiée 24h/24 et 7j/7 par chat et e-mail',
                    ],

                    'name' => 'Contenu des services',

                    'title' => [
                        'emi-available'   => 'Emi disponible',
                        'free-shipping'   => 'Livraison gratuite',
                        'product-replace' => 'Produit Remplacer',
                        'time-support'    => 'Assistance 24h/24 et 7j/7',
                    ],
                ],

                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Nos collections',
                        'sub-title-2' => 'Nos collections',
                        'sub-title-3' => 'Nos collections',
                        'sub-title-4' => 'Nos collections',
                        'sub-title-5' => 'Nos collections',
                        'sub-title-6' => 'Nos collections',
                        'title'       => 'Le jeu avec nos nouveaux ajouts!',
                    ],

                    'name' => 'Meilleures collections',
                ],
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'Les utilisateurs de ce rôle auront tous les accès',
                'name'        => 'Administrateur',
            ],

            'users' => [
                'name' => 'Exemple',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administrateur',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Confirmez le mot de passe',
                'email-address'    => 'admin@exemple.com',
                'email'            => 'E-mail',
                'password'         => 'Mot de passe',
                'title'            => 'Créer un administrateur',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Devises autorisées',
                'allowed-locales'     => 'Paramètres régionaux autorisés',
                'application-name'    => 'Nom de la demande',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Yuan chinois (CNY)',
                'database-connection' => 'Connexion à la base de données',
                'database-hostname'   => 'Nom d\'hôte de la base de données',
                'database-name'       => 'Nom de la base de données',
                'database-password'   => 'Mot de passe de la base de données',
                'database-port'       => 'Port de base de données',
                'database-prefix'     => 'Préfixe de base de données',
                'database-username'   => 'Nom d\'utilisateur de la base de données',
                'default-currency'    => 'Devise par défaut',
                'default-locale'      => 'Paramètres régionaux par défaut',
                'default-timezone'    => 'Fuseau horaire par défaut',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL par défaut',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'euros (EUR)',
                'iranian'             => 'Rial iranien (IRR)',
                'israeli'             => 'Shekel israélien (AFN)',
                'japanese-yen'        => 'Yen japonais (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Livre sterling (GBP)',
                'rupee'               => 'Roupie indienne (INR)',
                'russian-ruble'       => 'Rouble russe (RUB)',
                'saudi'               => 'Riyal saoudien (SAR)',
                'select-timezone'     => 'Sélectionnez le fuseau horaire',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Configuration de la base de données',
                'turkish-lira'        => 'Lire turque (TRY)',
                'ukrainian-hryvnia'   => 'Hryvnia ukrainienne (UAH)',
                'usd'                 => 'Dollar américain (USD)',
                'warning-message'     => 'Méfiez-vous! Les paramètres de vos langues système par défaut as well as the default currency are permanent and cannot be changed ever again.',
            ],

            'installation-processing' => [
                'unopim'      => 'Installation d\'UnoPim',
                'unopim-info' => 'Création des tables de la base de données, cela peut prendre quelques instants',
                'title'       => 'Installation',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Panneau d\'administration',
                'unopim-forums'             => 'Forum UnoPim',
                'explore-unopim-extensions' => 'Explorez l\'extension UnoPim',
                'title-info'                => 'UnoPim est installé avec succès sur votre système.',
                'title'                     => 'Installation terminée',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Créer la table de base de données',
                'install-info-button'     => 'Cliquez sur le bouton ci-dessous pour',
                'install-info'            => 'UnoPim pour l\'installation',
                'install'                 => 'Installation',
                'populate-database-table' => 'Remplir les tables de la base de données',
                'start-installation'      => 'Démarrer l\'installation',
                'title'                   => 'Prêt pour l\'installation',
            ],

            'start' => [
                'locale'        => 'Lieu',
                'main'          => 'Commencer',
                'select-locale' => 'Sélectionnez les paramètres régionaux',
                'title'         => 'Votre installation UnoPim',
                'welcome-title' => 'Bienvenue sur UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Calendrier',
                'ctype'       => 'cType',
                'curl'        => 'boucle',
                'dom'         => 'dom',
                'fileinfo'    => 'fichierInfo',
                'filter'      => 'Filtre',
                'gd'          => 'DG',
                'hash'        => 'Hacher',
                'intl'        => 'international',
                'json'        => 'JSON',
                'mbstring'    => 'chaînemb',
                'openssl'     => 'ouvressl',
                'pcre'        => 'PCRE',
                'pdo'         => 'aop',
                'php-version' => '8.2 ou supérieur',
                'php'         => 'PHP',
                'session'     => 'session',
                'title'       => 'Configuration système requise',
                'tokenizer'   => 'tokeniseur',
                'xml'         => 'XML',
            ],

            'back'                     => 'Dos',
            'unopim-info'              => 'un projet communautaire par',
            'unopim-logo'              => 'Logo UnoPim',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Continuer',
            'installation-description' => 'L\'installation d\'UnoPim implique généralement plusieurs étapes. Voici un aperçu général du processus d\'installation d\'UnoPim:',
            'wizard-language'          => 'Langue de l\'assistant d\'installation',
            'installation-info'        => 'Nous sommes heureux de vous voir ici!',
            'installation-title'       => 'Bienvenue dans l\'Installation',
            'save-configuration'       => 'Enregistrer la configuration',
            'skip'                     => 'Sauter',
            'title'                    => 'Programme d\'installation d\'UnoPim',
            'webkul'                   => 'Webkul',
        ],
    ],
];
