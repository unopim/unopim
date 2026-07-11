<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'Produkte',
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
            'title'      => 'Kategorien',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'Sie können die Stammkategorie, die einem Kanal zugeordnet ist, nicht löschen',
                ],
            ],
        ],
        'category-fields' => [
            'title'      => 'Kategoriefelder',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Der Code des Kategoriefeldes :code wird bereits verwendet.',
                    'code_not_found_to_delete' => 'Der Code des Kategoriefeldes wurde zum Löschen nicht gefunden.',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'Attribute',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributcode :code wird bereits verwendet.',
                    'code_not_found_to_delete'             => 'Attributcode zum Löschen nicht gefunden.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattribut kann nicht gelöscht werden.',
                ],
            ],
        ],
        'product-associations' => [
            'title'      => 'Produktverknüpfungen',
            'validation' => [
                'errors' => [
                    'required-field-missing'      => 'Das Feld \'%s\' ist erforderlich.',
                    'self-link-not-allowed'       => 'Das Produkt \'%s\' kann nicht mit sich selbst verknüpft werden.',
                    'sku-not-found'               => 'Produkt mit der SKU \'%s\' wurde nicht gefunden.',
                    'related-sku-not-found'       => 'Verknüpftes Produkt mit der SKU \'%s\' wurde nicht gefunden.',
                    'association-type-not-found'  => 'Der Verknüpfungstyp \'%s\' existiert nicht oder ist inaktiv.',
                    'invalid-field-value'         => 'Ungültiger Wert für ein Verknüpfungsfeld angegeben.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'Attributgruppen',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'Attributgruppencode :code wird bereits verwendet.',
                    'code_not_found_to_delete'             => 'Attributgruppencode zum Löschen nicht gefunden.',
                    'code_is_system_and_cannot_be_deleted' => 'Systemattributgruppe kann nicht gelöscht werden.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'Attributfamilien',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributfamiliencode :code wird bereits verwendet.',
                    'code_not_found_to_delete' => 'Attributfamiliencode zum Löschen nicht gefunden.',
                    'invalid-attribute-group'  => 'Attributgruppe ":code" existiert nicht.',
                    'invalid-attribute'        => 'Attribut ":code" existiert nicht.',
                    'invalid-channel'          => 'Kanal ":code" existiert nicht.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'Attributoptionen',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'Attributoptionscode :code wird bereits verwendet.',
                    'code_not_found_to_delete' => 'Attributoptionscode zum Löschen nicht gefunden.',
                    'locale-not-exist'         => 'Gebietsschema ":code" existiert nicht.',
                    'invalid-attribute'        => 'Attribut ":code" existiert nicht.',
                ],
            ],
        ],
        'locales' => [
            'title'      => 'Gebietsschemata',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Der Sprachcode \'%s\' wurde bereits in diesem Batch importiert.',
                    'code-not-found-to-delete'    => 'Sprache mit dem Code \'%s\' wurde im System nicht gefunden.',
                    'invalid-status'              => 'Der Status muss 0 oder 1 sein (oder leer für standardmäßig aktiviert).',
                    'channel-related-locale-root' => 'Sie können die Sprache mit dem Code :code nicht löschen, da sie mit einem Kanal verknüpft ist.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'Kanäle',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'Kanal mit dem Code :code wurde zum Löschen nicht gefunden.',
                    'locale-not-found'         => 'Eine oder mehrere Gebietsschemata existieren nicht.',
                    'root-category-not-found'  => 'Die Stammkategorie existiert nicht.',
                    'currency-not-found'       => 'Eine oder mehrere Währungen existieren nicht.',
                    'invalid-locale'           => 'Das Gebietsschema existiert nicht.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Währungen',
            'filters' => [
                'status' => 'Status',
                'enable' => 'Aktiviert',
                'all'    => 'Alle',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Der Status muss 0 oder 1 sein (oder leer für standardmäßig aktiviert).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Rollen',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Benutzer',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alle',
            ],
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
        'export-too-large' => 'Dieser Export ist zu groß: geschätzte :rows Zeilen × :columns Spalten (~:estimated) überschreiten den verfügbaren Speicherplatz (~:available). Schränken Sie den Export ein, indem Sie weniger Kanäle/Sprachen (und Attribute) auswählen, und versuchen Sie es erneut.',
        'fields'           => [
            'file-format'         => 'Dateiformat',
            'with-media'          => 'Mit Medien',
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
            'file-path'      => 'Dateipfad',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'Status',
            'enable'         => 'Aktiviert',
            'all'            => 'Alle',
        ],
        'products' => [
            'title'              => 'Produkte',
            'invalid-locales'    => 'Nicht alle ausgewählten Sprachen sind für die ausgewählten Kanäle verfügbar.',
            'invalid-currencies' => 'Nicht alle ausgewählten Währungen sind für die ausgewählten Kanäle verfügbar.',
            'filters'            => [
                'channels'             => 'Kanäle',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'Währungen',
                'currencies-info'      => 'Preisattribute werden je ausgewählter Währung exportiert. Leer lassen, um alle Kanalwährungen zu exportieren.',
                'locales'              => 'Sprachen',
                'locales-info'         => 'Lokalisierbare Attribute werden einmal je ausgewählter Sprache exportiert. Leer lassen, um alle Kanalsprachen zu exportieren.',
                'attributes'           => 'Attribute',
                'attributes-info'      => 'Es werden nur die ausgewählten Attribute exportiert. Leer lassen, um alle Attribute der Familie zu exportieren.',
                'attribute-families'   => 'Attributfamilien',
                'categories'           => 'Kategorien',
                'completeness'         => 'Vollständigkeit',
                'completeness-options' => [
                    'none'         => 'Keine Bedingung für Vollständigkeit',
                    'at-least-one' => 'Vollständig in mindestens einer ausgewählten Sprache',
                    'all'          => 'Vollständig in allen ausgewählten Sprachen',
                ],
                'time-condition' => 'Zeitbedingung',
                'time-options'   => [
                    'none'              => 'Keine Datumsbedingung',
                    'last-n-days'       => 'In den letzten N Tagen aktualisierte Produkte',
                    'between-dates'     => 'Zwischen zwei Daten aktualisierte Produkte',
                    'since-last-export' => 'Seit dem letzten Export aktualisierte Produkte',
                ],
                'time-value'     => 'Anzahl der Tage',
                'time-date'      => 'Startdatum',
                'time-date-end'  => 'Enddatum',
                'status'         => 'Status',
                'status-options' => [
                    'enable'  => 'Aktiviert',
                    'disable' => 'Deaktiviert',
                    'all'     => 'Alle',
                ],
                'sku'              => 'Artikelnummer',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'Bezeichner',
                'identifiers-info' => 'Fügen Sie pro Zeile eine SKU / einen Bezeichner ein, um nur diese Produkte zu exportieren. Leer lassen, um alle Produkte zu exportieren.',
            ],
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
            'title' => 'Kategorien',
        ],
        'category-fields' => [
            'title' => 'Kategoriefelder',
        ],
        'attributes' => [
            'title' => 'Attribute',
        ],
        'attribute-groups' => [
            'title' => 'Attributgruppen',
        ],
        'attribute-families' => [
            'title' => 'Attributfamilien',
        ],
        'attribute-options' => [
            'title' => 'Attributoptionen',
        ],
        'locales' => [
            'title' => 'Gebietsschemata',
        ],
        'channels' => [
            'title' => 'Kanäle',
        ],
        'currencies' => [
            'title' => 'Währungen',
        ],
        'roles' => [
            'title' => 'Rollen',
        ],
        'users' => [
            'title'   => 'Benutzer',
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'all'    => 'Alle',
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
            'file-empty'           => 'Die Datei ist leer oder enthält keine Kopfzeile. Bitte laden Sie eine gültige Datei mit Daten hoch.',
        ],
    ],
    'job' => [
        'started'   => 'Jobausführung gestartet',
        'completed' => 'Jobausführung abgeschlossen',
    ],
];
