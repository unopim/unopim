<?php

return [
    'importers' => [

        'products' => [
            'title' => 'Producten',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'url-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'Ongeldige waarde voor de attribuutfamiliekolom (attribuutfamilie bestaat niet?)',
                    'invalid-type'                             => 'Producttype is ongeldig of wordt niet ondersteund',
                    'sku-not-found'                            => 'Product met opgegeven SKU niet gevonden',
                    'super-attribute-not-found'                => 'Configureerbaar attribuut met code :code niet gevonden of behoort niet tot de attribuutfamilie :familyCode',
                    'configurable-attributes-not-found'        => 'Configureerbare attributen zijn vereist voor het maken van een productmodel',
                    'configurable-attributes-wrong-type'       => 'Alleen typekenmerken selecteren die niet op landinstellingen of kanalen zijn gebaseerd, mogen configureerbare kenmerken zijn voor een configureerbaar product',
                    'variant-configurable-attribute-not-found' => 'Variant configureerbaar attribuut :code is vereist voor het maken',
                    'not-unique-variant-product'               => 'Er bestaat al een product met dezelfde configureerbare kenmerken.',
                    'channel-not-exist'                        => 'Dit kanaal bestaat niet.',
                    'locale-not-in-channel'                    => 'Deze landinstelling is niet geselecteerd in het kanaal.',
                    'locale-not-exist'                         => 'Deze landinstelling bestaat niet',
                    'not-unique-value'                         => 'De :code waarde moet uniek zijn.',
                    'incorrect-family-for-variant'             => 'Het gezin moet hetzelfde zijn als het oudergezin',
                    'parent-not-exist'                         => 'De ouder bestaat niet.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'Categorieën',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'U kunt de hoofdcategorie die aan een kanaal is gekoppeld, niet verwijderen',
                ],
            ],
        ],
    ],

    'exporters' => [

        'products' => [
            'title' => 'Producten',

            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'URL-sleutel: \'%s\' is al gegenereerd voor een item met de SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'Ongeldige waarde voor de attribuutfamiliekolom (attribuutfamilie bestaat niet?)',
                    'invalid-type'              => 'Producttype is ongeldig of wordt niet ondersteund',
                    'sku-not-found'             => 'Product met opgegeven SKU niet gevonden',
                    'super-attribute-not-found' => 'Superattribuut met code: \'%s\' niet gevonden of behoort niet tot de attribuutfamilie: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'Categorieën',
        ],
    ],

    'validation' => [
        'errors' => [
            'column-empty-headers' => 'Kolommen nummer "%s" hebben lege kopteksten.',
            'column-name-invalid'  => 'Ongeldige kolomnamen: "%s".',
            'column-not-found'     => 'Vereiste kolommen niet gevonden: %s.',
            'column-numbers'       => 'Het aantal kolommen komt niet overeen met het aantal rijen in de koptekst.',
            'invalid-attribute'    => 'Header bevat ongeldig(e) attribuut(en): "%s".',
            'system'               => 'Er heeft zich een onverwachte systeemfout voorgedaan.',
            'wrong-quotes'         => 'Er worden gekrulde aanhalingstekens gebruikt in plaats van rechte aanhalingstekens.',
        ],
    ],

    'job' => [
        'started'   => 'De uitvoering van de werkzaamheden is begonnen',
        'completed' => 'Uitvoering van de taak voltooid',
    ],
];
