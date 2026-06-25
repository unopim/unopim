<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produse',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'Cheie URL: \'%s\' a fost deja generată pentru un articol cu SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Valoare invalidă pentru coloana familiei de atribute (familia de atribute nu există?)',
                    'invalid-type'                             => 'Tip de produs invalid sau neacceptat',
                    'sku-not-found'                            => 'Produsul cu SKU specificat nu a fost găsit',
                    'super-attribute-not-found'                => 'Atribut configurabil cu codul: \'%s\' nu a fost găsit sau nu aparține familiei de atribute: \'%s\' :code :familyCode',
                    'configurable-attributes-not-found'        => 'Atributele configurabile sunt necesare pentru a crea model de produs',
                    'configurable-attributes-wrong-type'       => 'Doar atributele de tip care nu se bazează pe locație sau canal pot fi atribute configurabile pentru un produs configurabil',
                    'variant-configurable-attribute-not-found' => 'Atribut configurabil variant: :code este necesar pentru a crea',
                    'not-unique-variant-product'               => 'Un produs cu aceleași atribute configurabile deja există.',
                    'channel-not-exist'                        => 'Canalul acesta nu există.',
                    'locale-not-in-channel'                    => 'Această locație nu este selectată în canal.',
                    'locale-not-exist'                         => 'Această locație nu există',
                    'not-unique-value'                         => 'Valoarea :code trebuie să fie unică.',
                    'incorrect-family-for-variant'             => 'Familia trebuie să fie aceeași cu familia principală',
                    'parent-not-exist'                         => 'Părintele nu există.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorii',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Nu poți șterge categoria rădăcină asociată unui canal',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Limbi',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Codul limbii \'%s\' a fost deja importat în acest lot.',
                    'code-not-found-to-delete'    => 'Limba cu codul \'%s\' nu a fost găsită în sistem.',
                    'invalid-status'              => 'Statusul trebuie să fie 0 sau 1 (sau gol pentru activat implicit).',
                    'channel-related-locale-root' => 'Nu puteți șterge limba cu codul :code deoarece este asociată unui canal.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Canale',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Canalul cu codul :code nu a fost găsit pentru ștergere.',
                    'locale-not-found'         => 'Una sau mai multe limbi nu există.',
                    'root-category-not-found'  => 'Categoria rădăcină nu există.',
                    'currency-not-found'       => 'Una sau mai multe valute nu există.',
                    'invalid-locale'           => 'Limba nu există.',
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
            'title'      => 'Produse',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'Cheie URL: \'%s\' a fost deja generată pentru un articol cu SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Valoare invalidă pentru coloana familiei de atribute (familia de atribute nu există?)',
                    'invalid-type'              => 'Tip de produs invalid sau neacceptat',
                    'sku-not-found'             => 'Produsul cu SKU specificat nu a fost găsit',
                    'super-attribute-not-found' => 'Atribut configurabil cu codul: \'%s\' nu a fost găsit sau nu aparține familiei de atribute: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorii',
        ],
        'locales' => [
            'title' => 'Limbi',
        ],
        'channels' => [
            'title' => 'Canale',
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
            'column-empty-headers' => 'Coloanele numărului "%s" au antete goale.',
            'column-name-invalid'  => 'Anteturi de coloane invalide: "%s".',
            'column-not-found'     => 'Coloanele necesare nu au fost găsite: %s.',
            'column-numbers'       => 'Numărul de coloane nu corespunde numărului de rânduri din antet.',
            'invalid-attribute'    => 'Antetul conține atribute invalide: "%s".',
            'system'               => 'A apărut o eroare sistemică neașteptată.',
            'wrong-quotes'         => 'A fost utilizată virgulă în loc de virgulă directă.',
            'file-empty'           => 'Fișierul este gol sau nu conține un rând de antet. Vă rugăm să încărcați un fișier valid cu date.',
        ],
    ],
    'job' => [
        'started'   => 'Start executie job',
        'completed' => 'Finalizare executie job',
    ],
];
