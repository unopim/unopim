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
        'fields' => [
            'file-format'         => 'Format fișier',
            'with-media'          => 'Cu media',
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
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Stare',
            'enable'         => 'Activat',
            'all'            => 'Toate',
        ],
        'products' => [
            'title'              => 'Produse',
            'invalid-locales'    => 'Nu toate limbile selectate sunt disponibile pentru canalele selectate.',
            'invalid-currencies' => 'Nu toate monedele selectate sunt disponibile pentru canalele selectate.',
            'filters'            => [
                'channels'             => 'Canale',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Monede',
                'currencies-info'      => 'Atributele de preț sunt exportate pentru fiecare monedă selectată. Lăsați gol pentru a exporta toate monedele canalului.',
                'locales'              => 'Localizări',
                'locales-info'         => 'Atributele localizabile sunt exportate o dată pentru fiecare limbă selectată. Lăsați gol pentru a exporta toate limbile canalului.',
                'attributes'           => 'Atribute',
                'attributes-info'      => 'Sunt exportate doar atributele selectate. Lăsați gol pentru a exporta toate atributele din familie.',
                'attribute-families'   => 'Familii de atribute',
                'categories'           => 'Categorii',
                'completeness'         => 'Completitudine',
                'completeness-options' => [
                    'none'         => 'Fără condiție de completitudine',
                    'at-least-one' => 'Complet în cel puțin o limbă selectată',
                    'all'          => 'Complet în toate limbile selectate',
                ],
                'time-condition' => 'Condiție de timp',
                'time-options'   => [
                    'none'              => 'Fără condiție de dată',
                    'last-n-days'       => 'Produse actualizate în ultimele N zile',
                    'between-dates'     => 'Produse actualizate între două date',
                    'since-last-export' => 'Produse actualizate de la ultimul export',
                ],
                'time-value'     => 'Număr de zile',
                'time-date'      => 'Data de început',
                'time-date-end'  => 'Data de sfârșit',
                'status'         => 'Stare',
                'status-options' => [
                    'enable'  => 'Activat',
                    'disable' => 'Dezactivat',
                    'all'     => 'Toate',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Identificatori',
                'identifiers-info' => 'Lipiți câte un SKU / identificator pe linie pentru a exporta doar acele produse. Lăsați gol pentru a exporta toate produsele.',
            ],
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
                'status' => 'Stare',
                'active' => 'Active',
                'all'    => 'Toate',
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
