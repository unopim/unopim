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
            'title'      => 'Currencies',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'      => 'Users',
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
        'products' => [
            'title'      => 'Produits',
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
        'attribute-groups' => [
            'title' => 'Groupes d\'attributs',
        ],
        'attribute-families' => [
            'title' => 'Familles d\'attributs',
        ],
        'attribute-options' => [
            'title' => 'Options d\'attribut',
        ],
        'channels' => [
            'title' => 'Canaux',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'L\'exécution du travail a commencé',
        'completed' => 'Exécution du travail terminée',
    ],
];
