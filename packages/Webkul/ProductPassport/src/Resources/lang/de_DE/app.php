<?php

return [
    'type' => [
        'label' => 'Digitaler Produktpass',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Produktpass',
            'info'     => 'Veröffentlichungseinstellungen für den digitalen Produktpass.',
            'settings' => [
                'title'                              => 'Produktpass-Einstellungen',
                'enabled'                            => 'Aktiviert',
                'enabled-hint'                       => 'Aktiviert die Funktion Digitaler Produktpass für diesen Katalog. Wenn deaktiviert, werden das Pass-Panel und das Raster ausgeblendet.',
                'auto-publish'                       => 'Beim Speichern automatisch veröffentlichen',
                'auto-publish-hint'                  => 'Veröffentlicht automatisch eine Pass-Version, sobald ein Produkt gespeichert wird und die Vollständigkeitsschwelle erreicht. Deaktiviert lassen, um manuell zu veröffentlichen.',
                'completeness-threshold'             => 'Vollständigkeitsschwelle (%)',
                'completeness-threshold-hint'        => 'Mindestvollständigkeit des Produkts in Prozent, die erforderlich ist, bevor ein Pass für eine Sprache veröffentlicht werden kann.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Name des Wirtschaftsakteurs',
                'operator-name-hint'                 => 'Rechtlicher Name des Herstellers oder verantwortlichen Wirtschaftsakteurs, der gemäß der ESPR-Verordnung auf jedem öffentlichen Pass angezeigt wird.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Anschrift des Wirtschaftsakteurs',
                'operator-address-hint'              => 'Eingetragene Postanschrift des Wirtschaftsakteurs, die zur Rückverfolgbarkeit auf dem öffentlichen Pass angezeigt wird.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'Bevollmächtigter in der EU',
                'operator-eu-rep-hint'               => 'Name und Kontakt des bevollmächtigten Vertreters in der EU, erforderlich, wenn der Hersteller außerhalb der EU niedergelassen ist.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'Support-URL',
                'support-url-hint'                   => 'Öffentliche Seite, auf der Kunden Hilfe oder Garantieinformationen finden. Wird als Link auf jedem Pass angezeigt.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Digitaler Produktpass',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Materialzusammensetzung',
        'dpp_substances_of_concern'     => 'Besorgniserregende Stoffe',
        'dpp_recycled_content_pct'      => 'Recyclinganteil (%)',
        'dpp_carbon_footprint'          => 'CO2-Fußabdruck',
        'dpp_energy_consumption'        => 'Energieverbrauch',
        'dpp_durability_statement'      => 'Haltbarkeitserklärung',
        'dpp_repairability_score'       => 'Reparierbarkeitswert',
        'dpp_spare_parts_availability'  => 'Verfügbarkeit von Ersatzteilen',
        'dpp_care_instructions'         => 'Pflegehinweise',
        'dpp_disassembly_guide'         => 'Demontageanleitung',
        'dpp_manufacturer_name'         => 'Herstellername',
        'dpp_manufacturing_site'        => 'Produktionsstandort',
        'dpp_country_of_origin'         => 'Ursprungsland',
        'dpp_supply_chain_notes'        => 'Hinweise zur Lieferkette',
        'dpp_end_of_life_instructions'  => 'Hinweise zur Entsorgung',
        'dpp_take_back_scheme'          => 'Rücknahmeprogramm',
        'dpp_declaration_of_conformity' => 'Konformitätserklärung',
        'dpp_test_reports'              => 'Prüfberichte',
        'dpp_certificates'              => 'Zertifikate',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Modellkennung',
        'dpp_batch_identifier'          => 'Chargenkennung',
        'dpp_warranty_terms'            => 'Garantiebedingungen',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Die Attribute des digitalen Produktpasses wurden erfolgreich installiert.',
        ],
    ],

    'public' => [
        'sections' => [
            'passport' => 'Produktpass',
        ],
        'title'         => 'Digitaler Produktpass',
        'badge'         => 'Digitaler EU-Produktpass',
        'search-locale' => 'Sprache suchen',
        'identifier'    => [
            'title'        => 'Identifikation',
            'gtin'         => 'GTIN',
            'model'        => 'Modell',
            'batch'        => 'Charge',
            'not-provided' => 'Nicht angegeben',
        ],
        'operator' => [
            'title' => 'Wirtschaftsakteur',
        ],
        'documents' => [
            'title' => 'Dokumente',
        ],
    ],

    'publications' => [
        'not-found'      => 'Kein Pass mit der ID :id gefunden.',
        'index'          => [
            'title'           => 'Digitale Produktpässe',
            'disabled-notice' => 'Die Veröffentlichung von Pässen ist derzeit deaktiviert. Vorhandene Pässe werden unten zur Verwaltung angezeigt (Ansehen und Zurückziehen).',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Kanal',
            'status'          => 'Status',
            'live-locales'    => 'Aktive Sprachen',
            'last-published'  => 'Zuletzt veröffentlicht',
            'withdraw'        => 'Zurückziehen',
            'mass-publish'    => 'Auswahl veröffentlichen',
        ],
        'publish-queued'      => 'Die Veröffentlichung des Passes wurde eingeplant.',
        'bulk-publish-queued' => 'Die Veröffentlichung der ausgewählten Pässe wurde in die Warteschlange gestellt.',
        'withdrawn'           => 'Pass erfolgreich zurückgezogen.',
        'mass-publish'        => [
            'action' => 'Digitalen Produktpass veröffentlichen',
            'queued' => 'Passveröffentlichung für :count Produkt(e) eingeplant.',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Pässe',
            'view'     => 'Anzeigen',
            'publish'  => 'Veröffentlichen',
            'withdraw' => 'Zurückziehen',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Pässe',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'title'                => 'Digitaler Produktpass',
                    'publishing-disabled'  => 'Die Veröffentlichung ist für diesen Kanal deaktiviert.',
                    'locale'               => 'Sprache',
                    'version'              => 'Version',
                    'published-at'         => 'Veröffentlicht am',
                    'missing-fields'       => 'Fehlende Felder',
                    'not-published'        => 'Nicht veröffentlicht',
                    'unscored'             => 'Nicht bewertet',
                    'publish'              => 'Veröffentlichen',
                    'republish'            => 'Erneut veröffentlichen',
                    'publish-all'          => 'Alle Sprachen veröffentlichen',
                    'auto-publish-on'      => 'Automatische Veröffentlichung ist aktiviert — Pässe werden automatisch veröffentlicht, wenn das Produkt gespeichert wird und den Vollständigkeitsschwellenwert erreicht. Verwenden Sie die Schaltflächen, um jetzt zu veröffentlichen.',
                    'auto-publish-off'     => 'Manuelle Veröffentlichung — verwenden Sie die Schaltflächen, um den Pass dieses Produkts für jede Sprache zu veröffentlichen.',
                    'publishing'           => 'Wird veröffentlicht…',
                    'queued'               => 'In Warteschlange',
                    'copy-operator-link'   => 'Betreiberlink kopieren',
                    'copy-authority-link'  => 'Behördenlink kopieren',
                    'link-copied'          => 'Link kopiert',
                    'download-qr'          => 'QR-Code herunterladen',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'Das Feld :attribute muss eine gültige GTIN sein (8, 12, 13 oder 14 Ziffern mit korrekter Prüfziffer).',
    ],
    'mapping' => [
        'title'         => 'Passfeld-Zuordnung',
        'info'          => 'Beziehen Sie jedes Passfeld aus einem Attribut, das Sie bereits pflegen. Lassen Sie ein Feld unzugeordnet, um auf sein eigenes Passattribut zurückzugreifen.',
        'menu'          => 'Feldzuordnung',
        'field'         => 'Passfeld',
        'source'        => 'Quellattribut',
        'select-source' => 'Passattribut verwenden',
        'save-btn'      => 'Zuordnung speichern',
        'type-mismatch' => 'Die ausgewählte Quelle ist nicht mit dem Typ dieses Passfelds kompatibel.',
        'saved'         => 'Feldzuordnung erfolgreich gespeichert.',
    ],

];
