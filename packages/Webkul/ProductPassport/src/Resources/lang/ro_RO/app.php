<?php

return [
    'type' => [
        'label' => 'Pașaport Digital al Produsului',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Pașaportul Produsului',
            'info'     => 'Setările de publicare a pașaportului digital al produsului.',
            'settings' => [
                'title'                              => 'Setări pașaport produs',
                'enabled'                            => 'Activat',
                'auto-publish'                       => 'Publică automat la salvare',
                'completeness-threshold'             => 'Prag de completitudine (%)',
                'operator-name'                      => 'Numele operatorului economic',
                'operator-address'                   => 'Adresa operatorului economic',
                'operator-eu-rep'                    => 'Reprezentant autorizat în UE',
                'support-url'                        => 'URL de asistență',
                'enabled-hint'                       => 'Activați funcția Pașaport Digital al Produsului pentru acest catalog. Când este dezactivată, panoul și grila de pașapoarte sunt ascunse.',
                'auto-publish-hint'                  => 'Publicați automat o versiune de pașaport ori de câte ori un produs este salvat și atinge pragul de completitudine. Lăsați dezactivat pentru a publica manual.',
                'completeness-threshold-hint'        => 'Completitudinea minimă a produsului, exprimată în procente, necesară înainte ca un pașaport să poată fi publicat pentru o localizare.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Denumirea legală a producătorului sau a operatorului economic responsabil, afișată pe fiecare pașaport public conform cerințelor regulamentului ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Adresa poștală înregistrată a operatorului economic, afișată pe pașaportul public pentru trasabilitate.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Numele și datele de contact ale reprezentantului autorizat în UE, necesare atunci când producătorul este stabilit în afara UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Pagină publică unde clienții pot găsi ajutor sau informații despre garanție. Afișată ca link pe fiecare pașaport.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Pașaport Digital al Produsului',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Compoziția materialului',
        'dpp_substances_of_concern'     => 'Substanțe îngrijorătoare',
        'dpp_recycled_content_pct'      => 'Conținut reciclat (%)',
        'dpp_carbon_footprint'          => 'Amprentă de carbon',
        'dpp_energy_consumption'        => 'Consum de energie',
        'dpp_durability_statement'      => 'Declarație de durabilitate',
        'dpp_repairability_score'       => 'Scor de reparabilitate',
        'dpp_spare_parts_availability'  => 'Disponibilitatea pieselor de schimb',
        'dpp_care_instructions'         => 'Instrucțiuni de îngrijire',
        'dpp_disassembly_guide'         => 'Ghid de dezasamblare',
        'dpp_manufacturer_name'         => 'Numele producătorului',
        'dpp_manufacturing_site'        => 'Locul de fabricație',
        'dpp_country_of_origin'         => 'Țara de origine',
        'dpp_supply_chain_notes'        => 'Note privind lanțul de aprovizionare',
        'dpp_end_of_life_instructions'  => 'Instrucțiuni de sfârșit de viață',
        'dpp_take_back_scheme'          => 'Schemă de returnare',
        'dpp_declaration_of_conformity' => 'Declarație de conformitate',
        'dpp_test_reports'              => 'Rapoarte de testare',
        'dpp_certificates'              => 'Certificate',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identificator model',
        'dpp_batch_identifier'          => 'Identificator lot',
        'dpp_warranty_terms'            => 'Condiții de garanție',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Atributele Pașaportului Digital al Produsului au fost instalate cu succes.',
        ],
    ],

    'public' => [
        'badge'         => 'Pașaport Digital de Produs EU',
        'search-locale' => 'Limba de căutare',
        'sections'      => [
            'passport' => 'Pașaportul Produsului',
        ],
        'title'      => 'Pașaport Digital al Produsului',
        'identifier' => [
            'title'        => 'Identificare',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Lot',
            'not-provided' => 'Nefurnizat',
        ],
        'operator' => [
            'title' => 'Operator economic',
        ],
        'documents' => [
            'title' => 'Documente',
        ],
    ],

    'publications' => [
        'not-found'      => 'Nu s-a găsit niciun pașaport pentru id-ul :id.',
        'index'          => [
            'disabled-notice' => 'Publicarea pașapoartelor este momentan dezactivată. Pașapoartele existente sunt afișate mai jos pentru gestionare (vizualizare și retragere).',
            'title'           => 'Pașapoarte Digitale ale Produsului',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Canal',
            'status'          => 'Stare',
            'live-locales'    => 'Limbi active',
            'last-published'  => 'Ultima publicare',
            'withdraw'        => 'Retrage',
        ],
        'publish-queued' => 'Publicarea pașaportului a fost pusă în coadă.',
        'withdrawn'      => 'Pașaport retras cu succes.',
        'mass-publish'   => [
            'action' => 'Publică Pașaportul Digital al Produsului',
            'queued' => 'Publicarea pașaportului a fost pusă în coadă pentru :count produs(e).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pașapoarte',
            'view'     => 'Vizualizare',
            'publish'  => 'Publică',
            'withdraw' => 'Retrage',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pașapoarte',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Se publică…',
                    'queued'               => 'În coadă',
                    'copy-operator-link'   => 'Copiați linkul operatorului',
                    'copy-authority-link'  => 'Copiați linkul autorității',
                    'link-copied'          => 'Link copiat',
                    'download-qr'          => 'Descarcă codul QR',
                    'title'                => 'Pașaport Digital al Produsului',
                    'publishing-disabled'  => 'Publicarea pașapoartelor este dezactivată pentru acest canal.',
                    'locale'               => 'Limbă',
                    'version'              => 'Versiune',
                    'published-at'         => 'Publicat la',
                    'missing-fields'       => 'Câmpuri lipsă',
                    'not-published'        => 'Nepublicat',
                    'unscored'             => 'Neevaluat',
                    'publish'              => 'Publică',
                    'republish'            => 'Republică',
                    'publish-all'          => 'Publică toate limbile',
                    'auto-publish-on'      => 'Publicarea automată este activată — pașapoartele sunt publicate automat când produsul este salvat și atinge pragul de completitudine. Folosește butoanele pentru a publica acum.',
                    'auto-publish-off'     => 'Publicare manuală — folosește butoanele pentru a publica pașaportul acestui produs pentru fiecare limbă.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Câmpul :attribute trebuie să fie un GTIN valid (8, 12, 13 sau 14 cifre cu o cifră de control corectă).',
    ],
    'mapping' => [
        'title'         => 'Maparea câmpurilor pașaportului',
        'info'          => 'Alimentați fiecare câmp al pașaportului dintr-un atribut pe care îl gestionați deja. Lăsați un câmp nemapat pentru a reveni la atributul său de pașaport dedicat.',
        'menu'          => 'Maparea câmpurilor',
        'field'         => 'Câmp pașaport',
        'source'        => 'Atribut sursă',
        'select-source' => 'Folosește atributul pașaportului',
        'save-btn'      => 'Salvează maparea',
        'type-mismatch' => 'Sursa selectată nu este compatibilă cu tipul acestui câmp de pașaport.',
        'saved'         => 'Maparea câmpurilor a fost salvată cu succes.',
    ],

];
