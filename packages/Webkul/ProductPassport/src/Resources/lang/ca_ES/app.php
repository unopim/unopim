<?php

return [
    'type' => [
        'label' => 'Passaport Digital del Producte',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaport del Producte',
            'info'     => 'Configuració de publicació del Passaport Digital del Producte.',
            'settings' => [
                'title'                              => 'Configuració del passaport del producte',
                'enabled'                            => 'Activat',
                'enabled-hint'                       => 'Activa la funció de Passaport Digital de Producte per a aquest catàleg. Quan està desactivada, el tauler i la graella de passaports queden ocults.',
                'auto-publish'                       => 'Publica automàticament en desar',
                'auto-publish-hint'                  => 'Publica automàticament una versió del passaport cada vegada que es desa un producte i assoleix el llindar de compleció. Deixeu-ho desactivat per publicar manualment.',
                'completeness-threshold'             => 'Llindar de compleció (%)',
                'completeness-threshold-hint'        => 'Compleció mínima del producte, en percentatge, necessària abans que es pugui publicar un passaport per a una configuració regional.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Nom de l\'operador econòmic',
                'operator-name-hint'                 => 'Nom legal del fabricant o de l\'operador econòmic responsable, mostrat a cada passaport públic tal com exigeix el reglament ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Adreça de l\'operador econòmic',
                'operator-address-hint'              => 'Adreça postal registrada de l\'operador econòmic, mostrada al passaport públic per a la traçabilitat.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Representant autoritzat a la UE',
                'operator-eu-rep-hint'               => 'Nom i contacte del representant autoritzat a la UE, necessari quan el fabricant està establert fora de la UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'URL de suport',
                'support-url-hint'                   => 'Pàgina pública on els clients poden trobar ajuda o informació de garantia. Es mostra com un enllaç a cada passaport.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Passaport Digital del Producte',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Composició del material',
        'dpp_substances_of_concern'     => 'Substàncies preocupants',
        'dpp_recycled_content_pct'      => 'Contingut reciclat (%)',
        'dpp_carbon_footprint'          => 'Petjada de carboni',
        'dpp_energy_consumption'        => 'Consum d\'energia',
        'dpp_durability_statement'      => 'Declaració de durabilitat',
        'dpp_repairability_score'       => 'Puntuació de reparabilitat',
        'dpp_spare_parts_availability'  => 'Disponibilitat de peces de recanvi',
        'dpp_care_instructions'         => 'Instruccions de cura',
        'dpp_disassembly_guide'         => 'Guia de desmuntatge',
        'dpp_manufacturer_name'         => 'Nom del fabricant',
        'dpp_manufacturing_site'        => 'Lloc de fabricació',
        'dpp_country_of_origin'         => 'País d\'origen',
        'dpp_supply_chain_notes'        => 'Notes de la cadena de subministrament',
        'dpp_end_of_life_instructions'  => 'Instruccions de fi de vida',
        'dpp_take_back_scheme'          => 'Programa de devolució',
        'dpp_declaration_of_conformity' => 'Declaració de conformitat',
        'dpp_test_reports'              => 'Informes de proves',
        'dpp_certificates'              => 'Certificats',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identificador de model',
        'dpp_batch_identifier'          => 'Identificador de lot',
        'dpp_warranty_terms'            => 'Condicions de garantia',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Els atributs del Passaport Digital del Producte s\'han instal·lat correctament.',
        ],
    ],

    'public' => [
        'badge'         => 'Passaport Digital de Producte EU',
        'search-locale' => 'Idioma de cerca',
        'sections'      => [
            'passport' => 'Passaport del Producte',
        ],
        'title'      => 'Passaport Digital del Producte',
        'identifier' => [
            'title'        => 'Identificació',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Lot',
            'not-provided' => 'No proporcionat',
        ],
        'operator' => [
            'title' => 'Operador econòmic',
        ],
        'documents' => [
            'title' => 'Documents',
        ],
    ],

    'publications' => [
        'index' => [
            'disabled-notice' => 'La publicació de passaports està desactivada actualment. Els passaports existents es mostren a continuació per gestionar-los (visualitzar i retirar).',
            'title'           => 'Passaports Digitals del Producte',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Canal',
            'status'          => 'Estat',
            'live-locales'    => 'Idiomes actius',
            'last-published'  => 'Última publicació',
            'withdraw'        => 'Retirar',
        ],
        'publish-queued' => 'S\'ha posat en cua la publicació del passaport.',
        'withdrawn'      => 'Passaport retirat correctament.',
        'mass-publish'   => [
            'action' => 'Publicar el passaport digital de producte',
            'queued' => 'Publicació del passaport en cua per a :count producte(s).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Passaports',
            'view'     => 'Veure',
            'publish'  => 'Publicar',
            'withdraw' => 'Retirar',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passaports',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'          => 'Publicant…',
                    'queued'              => 'A la cua',
                    'title'               => 'Passaport Digital del Producte',
                    'publishing-disabled' => 'La publicació de passaports està desactivada per a aquest canal.',
                    'locale'              => 'Idioma',
                    'version'             => 'Versió',
                    'published-at'        => 'Publicat el',
                    'missing-fields'      => 'Camps pendents',
                    'not-published'       => 'No publicat',
                    'unscored'            => 'Sense puntuar',
                    'publish'             => 'Publicar',
                    'republish'           => 'Torna a publicar',
                    'publish-all'         => 'Publica tots els idiomes',
                    'auto-publish-on'     => 'La publicació automàtica està activada — els passaports es publiquen automàticament quan el producte es desa i compleix el llindar de completesa. Utilitza els botons per publicar ara.',
                    'auto-publish-off'    => 'Publicació manual — utilitza els botons per publicar el passaport d\'aquest producte per a cada idioma.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'El :attribute ha de ser un GTIN vàlid (8, 12, 13 o 14 dígits amb un dígit de control correcte).',
    ],
    'mapping' => [
        'title' => 'Mapatge de camps del passaport',
        'info' => 'Obteniu cada camp del passaport a partir d\'un atribut que ja manteniu. Deixeu un camp sense mapar per utilitzar el seu atribut de passaport dedicat.',
        'menu' => 'Mapatge de camps',
        'field' => 'Camp del passaport',
        'source' => 'Atribut origen',
        'select-source' => 'Utilitza l\'atribut del passaport',
        'save-btn' => 'Desa el mapatge',
        'type-mismatch' => 'La font seleccionada no és compatible amb el tipus d\'aquest camp del passaport.',
        'saved' => 'El mapatge de camps s\'ha desat correctament.',
    ],

];
