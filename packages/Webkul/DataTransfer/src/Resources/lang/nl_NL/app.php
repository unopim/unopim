<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Producten',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'URL-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ongeldige waarde voor de attribuutsetkolom (attribuutset bestaat niet?).',
                    'invalid-type'                             => 'Producttype is ongeldig of wordt niet ondersteund.',
                    'sku-not-found'                            => 'Product met opgegeven SKU niet gevonden.',
                    'super-attribute-not-found'                => 'Configureerbaar attribuut met code :code niet gevonden of behoort niet tot de attribuutset :familyCode.',
                    'configurable-attributes-not-found'        => 'Configureerbare attributen zijn vereist voor het aanmaken van een productmodel.',
                    'configurable-attributes-wrong-type'       => 'Alleen attributen die niet op taalinstellingen of kanalen zijn gebaseerd, mogen configureerbare attributen zijn voor een configureerbaar product.',
                    'variant-configurable-attribute-not-found' => 'Configureerbaar attribuut :code is vereist voor het aanmaken van de variant.',
                    'not-unique-variant-product'               => 'Er bestaat al een product met dezelfde configureerbare attributen.',
                    'channel-not-exist'                        => 'Dit kanaal bestaat niet.',
                    'locale-not-in-channel'                    => 'Deze taalinstelling is niet geselecteerd in het kanaal.',
                    'locale-not-exist'                         => 'Deze taalinstelling bestaat niet.',
                    'not-unique-value'                         => 'De waarde van :code moet uniek zijn.',
                    'incorrect-family-for-variant'             => 'De attribuutset moet gelijk zijn aan die van het hoofdproduct.',
                    'parent-not-exist'                         => 'Het hoofdproduct bestaat niet.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorieën',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Je kunt de hoofdcategorie die aan een kanaal is gekoppeld niet verwijderen.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanalen',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanaal met code :code niet gevonden om te verwijderen.',
                    'locale-not-found'         => 'Een of meer talen bestaan niet.',
                    'root-category-not-found'  => 'De hoofdcategorie bestaat niet.',
                    'currency-not-found'       => 'Een of meer valuta bestaan niet.',
                    'invalid-locale'           => 'De taal bestaat niet.',
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
        'locales' => [
            'title'      => 'Talen',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'De taalcode \'%s\' is al geïmporteerd in deze batch.',
                    'code-not-found-to-delete'    => 'Taal met code \'%s\' niet gevonden in het systeem.',
                    'invalid-status'              => 'Status moet 0 of 1 zijn (of leeg voor standaard ingeschakeld).',
                    'channel-related-locale-root' => 'Je kunt de taal met code :code niet verwijderen omdat deze is gekoppeld aan een kanaal.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'Producten',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ongeldige waarde voor de attribuutsetkolom (attribuutset bestaat niet?).',
                    'invalid-type'              => 'Producttype is ongeldig of wordt niet ondersteund.',
                    'sku-not-found'             => 'Product met opgegeven SKU niet gevonden.',
                    'super-attribute-not-found' => 'Configureerbaar attribuut met code \'%s\' niet gevonden of behoort niet tot de attribuutset \'%s\'.',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorieën',
        ],
        'locales' => [
            'title' => 'Talen',
        ],
        'channels' => [
            'title' => 'Kanalen',
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
            'column-empty-headers' => 'Kolom(men) met nummer "%s" hebben lege kopteksten.',
            'column-name-invalid'  => 'Ongeldige kolomnamen: "%s".',
            'column-not-found'     => 'Vereiste kolommen niet gevonden: %s.',
            'column-numbers'       => 'Het aantal kolommen komt niet overeen met het aantal rijen in de koptekst.',
            'invalid-attribute'    => 'Koptekst bevat ongeldig(e) attribuut(en): "%s".',
            'system'               => 'Er is een onverwachte systeemfout opgetreden.',
            'wrong-quotes'         => 'Er worden gekrulde aanhalingstekens gebruikt in plaats van rechte aanhalingstekens.',
            'file-empty'           => 'Het bestand is leeg of bevat geen kopregel. Upload een geldig bestand met gegevens.',
        ],
    ],
    'job' => [
        'started'   => 'De taak is gestart.',
        'completed' => 'Taak succesvol voltooid.',
    ],
];
