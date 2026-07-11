<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produits',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL-Schlüssel: \'%s\' wurde bereits für einen Artikel mit der SKU: \'%s\' generiert.',
                    'invalid-attribute-family'                 => 'Ungültiger Wert für die Spalte „Attributfamilie“ (Attributfamilie existiert nicht?)',
                    'invalid-type'                             => 'Der Produkttyp ist ungültig oder wird nicht unterstützt',
                    'sku-not-found'                            => 'Produkt mit der angegebenen SKU nicht gefunden',
                    'super-attribute-not-found'                => 'Konfigurierbares Attribut mit Code :code nicht gefunden oder gehört nicht zur Attributfamilie :familyCode',
                    'configurable-attributes-not-found'        => 'Für die Erstellung eines Produktmodells sind konfigurierbare Attribute erforderlich',
                    'configurable-attributes-wrong-type'       => 'Nur ausgewählte Typattribute, die nicht auf dem Gebietsschema oder Kanal basieren, dürfen konfigurierbare Attribute für ein konfigurierbares Produkt sein',
                    'variant-configurable-attribute-not-found' => 'Für die Erstellung ist ein konfigurierbares Variantenattribut :code erforderlich',
                    'not-unique-variant-product'               => 'Ein Produkt mit denselben konfigurierbaren Attributen ist bereits vorhanden.',
                    'channel-not-exist'                        => 'Dieser Kanal existiert nicht.',
                    'locale-not-in-channel'                    => 'Dieses Gebietsschema ist im Kanal nicht ausgewählt.',
                    'locale-not-exist'                         => 'Dieses Gebietsschema existiert nicht',
                    'not-unique-value'                         => 'Der :code-Wert muss eindeutig sein.',
                    'incorrect-family-for-variant'             => 'Die Familie muss mit der Elternfamilie identisch sein',
                    'parent-not-exist'                         => 'Das übergeordnete Element existiert nicht.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Catégories',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Sie können die Stammkategorie, die einem Kanal zugeordnet ist, nicht löschen',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Champs de catégorie',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Le code du champ de catégorie :code est déjà utilisé.',
                    'code_not_found_to_delete' => 'Le code du champ de catégorie n\'a pas été trouvé pour suppression.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Le code d\'attribut :code est déjà utilisé.',
                    'code_not_found_to_delete'             => 'Code d\'attribut introuvable pour la suppression.',
                    'code_is_system_and_cannot_be_deleted' => 'L\'attribut système ne peut pas être supprimé.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Associations de produits',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Le champ \'%s\' est obligatoire.',
                    'self-link-not-allowed'       => 'Le produit \'%s\' ne peut pas être associé à lui-même.',
                    'sku-not-found'               => 'Produit avec le SKU \'%s\' introuvable.',
                    'related-sku-not-found'       => 'Produit associé avec le SKU \'%s\' introuvable.',
                    'association-type-not-found'  => 'Le type d\'association \'%s\' n\'existe pas ou est inactif.',
                    'invalid-field-value'         => 'Valeur invalide fournie pour un champ d\'association.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Groupes d\'attributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Le code du groupe d\'attributs :code est déjà utilisé.',
                    'code_not_found_to_delete'             => 'Code du groupe d\'attributs introuvable pour la suppression.',
                    'code_is_system_and_cannot_be_deleted' => 'Le groupe d\'attributs système ne peut pas être supprimé.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Familles d\'attributs',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Le code de la famille d\'attributs :code est déjà utilisé.',
                    'code_not_found_to_delete' => 'Code de la famille d\'attributs introuvable pour la suppression.',
                    'invalid-attribute-group'  => 'Le groupe d\'attributs ":code" n\'existe pas.',
                    'invalid-attribute'        => 'L\'attribut ":code" n\'existe pas.',
                    'invalid-channel'          => 'Le canal ":code" n\'existe pas.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Options d\'attribut',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Le code de l\'option d\'attribut :code est déjà utilisé.',
                    'code_not_found_to_delete' => 'Code de l\'option d\'attribut introuvable pour la suppression.',
                    'locale-not-exist'         => 'La langue ":code" n\'existe pas.',
                    'invalid-attribute'        => 'L\'attribut ":code" n\'existe pas.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Langues',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Le code de langue \'%s\' a déjà été importé dans ce lot.',
                    'code-not-found-to-delete'    => 'Aucune langue avec le code \'%s\' trouvée dans le système.',
                    'invalid-status'              => 'Le statut doit être 0 ou 1 (ou vide pour activé par défaut).',
                    'channel-related-locale-root' => 'Vous ne pouvez pas supprimer la langue avec le code :code car elle est associée à un canal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canaux',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Le canal avec le code :code est introuvable pour suppression.',
                    'locale-not-found'         => 'Une ou plusieurs langues n\'existent pas.',
                    'root-category-not-found'  => 'La catégorie racine n\'existe pas.',
                    'currency-not-found'       => 'Une ou plusieurs devises n\'existent pas.',
                    'invalid-locale'           => 'La langue n\'existe pas.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Devises',
            'filters' => [
                'status' => 'Statut',
                'enable' => 'Activé',
                'all'    => 'Tous',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Le statut doit être 0 ou 1 (ou vide pour activé par défaut).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Rôles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Utilisateurs',
            'filters' => [
                'status' => 'Statut',
                'active' => 'Actif',
                'all'    => 'Tous',
            ],
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'Cet export est trop volumineux pour être exécuté : environ :rows lignes × :columns colonnes (~:estimated) dépassent l\'espace disponible (~:available). Réduisez l\'export en sélectionnant moins de canaux/locales (et d\'attributs), puis réessayez.',
        'fields'           => [
            'file-format'         => 'Format de fichier',
            'with-media'          => 'Avec médias',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'Chemin du fichier',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Statut',
            'enable'         => 'Activé',
            'all'            => 'Tous',
        ],
        'products' => [
            'title'              => 'Produits',
            'invalid-locales'    => 'Toutes les langues sélectionnées ne sont pas disponibles pour les canaux sélectionnés.',
            'invalid-currencies' => 'Toutes les devises sélectionnées ne sont pas disponibles pour les canaux sélectionnés.',
            'filters'            => [
                'channels'             => 'Canaux',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Devises',
                'currencies-info'      => 'Les attributs de prix sont exportés par devise sélectionnée. Laissez vide pour exporter toutes les devises du canal.',
                'locales'              => 'Langues',
                'locales-info'         => 'Les attributs localisables sont exportés une fois par langue sélectionnée. Laissez vide pour exporter toutes les langues du canal.',
                'attributes'           => 'Attributs',
                'attributes-info'      => 'Seuls les attributs sélectionnés sont exportés. Laissez vide pour exporter tous les attributs de la famille.',
                'attribute-families'   => 'Familles d\'attributs',
                'categories'           => 'Catégories',
                'completeness'         => 'Complétude',
                'completeness-options' => [
                    'none'         => 'Aucune condition de complétude',
                    'at-least-one' => 'Complet dans au moins une langue sélectionnée',
                    'all'          => 'Complet dans toutes les langues sélectionnées',
                ],
                'time-condition' => 'Condition temporelle',
                'time-options'   => [
                    'none'              => 'Aucune condition de date',
                    'last-n-days'       => 'Produits mis à jour au cours des N derniers jours',
                    'between-dates'     => 'Produits mis à jour entre deux dates',
                    'since-last-export' => 'Produits mis à jour depuis le dernier export',
                ],
                'time-value'     => 'Nombre de jours',
                'time-date'      => 'Date de début',
                'time-date-end'  => 'Date de fin',
                'status'         => 'Statut',
                'status-options' => [
                    'enable'  => 'Activé',
                    'disable' => 'Désactivé',
                    'all'     => 'Tous',
                ],
                'sku'              => 'Sku',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identifiants',
                'identifiers-info' => 'Collez un SKU / identifiant par ligne pour exporter uniquement ces produits. Laissez vide pour exporter tous les produits.',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-Schlüssel: \'%s\' wurde bereits für einen Artikel mit der SKU: \'%s\' generiert.',
                    'invalid-attribute-family'  => 'Ungültiger Wert für die Spalte „Attributfamilie“ (Attributfamilie existiert nicht?)',
                    'invalid-type'              => 'Der Produkttyp ist ungültig oder wird nicht unterstützt',
                    'sku-not-found'             => 'Produkt mit der angegebenen SKU nicht gefunden',
                    'super-attribute-not-found' => 'Superattribut mit Code: \'%s\' nicht gefunden oder gehört nicht zur Attributfamilie: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Catégories',
        ],
        'category-fields' => [
            'title' => 'Champs de catégorie',
        ],
        'attributes' => [
            'title' => 'Attributs',
        ],
        'product-associations' => [
            'title' => 'Associations de produits',
        ],
        'attribute-groups' => [
            'title' => 'Groupes d\'attributs',
        ],
        'attribute-families' => [
            'title' => 'Familles d\'attributs',
        ],
        'attribute-options' => [
            'title' => 'Options d\'attribut',
        ],
        'locales' => [
            'title' => 'Langues',
        ],
        'channels' => [
            'title' => 'Canaux',
        ],
        'currencies' => [
            'title' => 'Devises',
        ],
        'roles' => [
            'title' => 'Rôles',
        ],
        'users' => [
            'title'   => 'Utilisateurs',
            'filters' => [
                'status' => 'Statut',
                'active' => 'Actif',
                'all'    => 'Tous',
            ],
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Die Spalten mit der Nummer „%s“ haben leere Kopfzeilen.',
            'column-name-invalid'  => 'Ungültige Spaltennamen: „%s“.',
            'column-not-found'     => 'Erforderliche Spalten nicht gefunden: %s.',
            'column-numbers'       => 'Die Anzahl der Spalten entspricht nicht der Anzahl der Zeilen in der Kopfzeile.',
            'invalid-attribute'    => 'Header enthält ungültige Attribute: „%s“.',
            'system'               => 'Es ist ein unerwarteter Systemfehler aufgetreten.',
            'wrong-quotes'         => 'Anstelle von geraden Anführungszeichen werden geschweifte Anführungszeichen verwendet.',
            'file-empty'           => 'Le fichier est vide ou ne contient pas de ligne d\'en-tête. Veuillez télécharger un fichier valide contenant des données.',
        ],
    ],
    'job' => [
        'started'   => 'L\'exécution du travail a commencé',
        'completed' => 'Exécution du travail terminée',
    ],
];
