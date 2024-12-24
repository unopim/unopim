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
                    'super-attribute-not-found'                => 'Atribut configurabil cu codul: \'%s\' nu a fost găsit sau nu aparține familiei de atribute: \'%s\'',
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
        ],
    ],
    'job' => [
        'started'   => 'Start executie job',
        'completed' => 'Finalizare executie job',
    ],
];
