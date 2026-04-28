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
        'category-fields' => [
            'title'      => 'Câmpuri categorie',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Codul câmpului de categorie :code este deja utilizat.',
                    'code_not_found_to_delete' => 'Codul câmpului de categorie nu a fost găsit pentru ștergere.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Atribute',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Codul atributului :code este deja utilizat.',
                    'code_not_found_to_delete'             => 'Codul atributului nu a fost găsit pentru ștergere.',
                    'code_is_system_and_cannot_be_deleted' => 'Atributul de sistem nu poate fi șters.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Grupuri de atribute',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Codul grupului de atribute :code este deja utilizat.',
                    'code_not_found_to_delete'             => 'Codul grupului de atribute nu a fost găsit pentru ștergere.',
                    'code_is_system_and_cannot_be_deleted' => 'Grupul de atribute de sistem nu poate fi șters.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Familii de atribute',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Codul familiei de atribute :code este deja utilizat.',
                    'code_not_found_to_delete' => 'Codul familiei de atribute nu a fost găsit pentru ștergere.',
                    'invalid-attribute-group'  => 'Grupul de atribute ":code" nu există.',
                    'invalid-attribute'        => 'Atributul ":code" nu există.',
                    'invalid-channel'          => 'Canalul ":code" nu există.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Opțiuni atribute',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Codul opțiunii atributului :code este deja utilizat.',
                    'code_not_found_to_delete' => 'Codul opțiunii atributului nu a fost găsit pentru ștergere.',
                    'locale-not-exist'         => 'Numele de localizare ":code" nu există.',
                    'invalid-attribute'        => 'Atributul ":code" nu există.',
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
        'category-fields' => [
            'title' => 'Câmpuri categorie',
        ],
        'attributes' => [
            'title' => 'Atribute',
        ],
        'attribute-groups' => [
            'title' => 'Grupuri de atribute',
        ],
        'attribute-families' => [
            'title' => 'Familii de atribute',
        ],
        'attribute-options' => [
            'title' => 'Opțiuni atribute',
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
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'Start executie job',
        'completed' => 'Finalizare executie job',
    ],
];
