<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhooks',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Bitte aktivieren Sie den Webhook in den Einstellungen',
        'success'       => 'Die Produktdaten wurden erfolgreich an den Webhook gesendet',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Erstellen',
            'edit'   => 'Bearbeiten',
            'delete' => 'Löschen',
        ],
        'settings' => [
            'index'  => 'Einstellungen',
            'update' => 'Einstellungen aktualisieren',
        ],
        'logs' => [
            'index'       => 'Protokolle',
            'view'        => 'Ansehen',
            'delete'      => 'Löschen',
            'mass-delete' => 'Massenlöschung',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produkt erstellt',
            'updated' => 'Produkt aktualisiert',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Webhook erstellen',
            'logs-btn'     => 'Protokolle',
            'back-btn'     => 'Zurück zu Webhooks',
            'default-name' => 'Standard',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Name',
                'url'        => 'URL',
                'events'     => 'Ereignisse',
                'status'     => 'Status',
                'active'     => 'Aktiv',
                'inactive'   => 'Inaktiv',
                'created_at' => 'Erstellt am',
                'edit'       => 'Bearbeiten',
                'delete'     => 'Löschen',
            ],
        ],
        'create' => [
            'title'    => 'Webhook erstellen',
            'cancel'   => 'Abbrechen',
            'save-btn' => 'Speichern',
        ],
        'edit' => [
            'title'    => 'Webhook bearbeiten',
            'cancel'   => 'Abbrechen',
            'save-btn' => 'Speichern',
        ],
        'form' => [
            'general'       => 'Allgemein',
            'name'          => 'Name',
            'url'           => 'URL',
            'events'        => 'Ereignisse',
            'select-events' => 'Ereignisse auswählen',
            'secret'        => 'Signaturgeheimnis',
            'secret-set'    => 'Ein Geheimnis ist bereits festgelegt',
            'secret-hint'   => 'Wird verwendet, um jede Nutzlast mit einer HMAC-SHA-256-Signatur zu signieren. Leer lassen, um das aktuelle Geheimnis beizubehalten.',
            'settings'      => 'Einstellungen',
            'active'        => 'Aktiv',
            'test'          => 'Verbindung testen',
            'test-hint'     => 'Senden Sie eine Testanfrage an die obige URL.',
            'test-btn'      => 'Test senden',
            'test-no-url'   => 'Bitte geben Sie zuerst eine URL ein.',
            'test-failed'   => 'Die Testanfrage ist fehlgeschlagen.',
            'headers'       => 'Benutzerdefinierte Header',
            'add-header'    => 'Header hinzufügen',
            'no-headers'    => 'Keine benutzerdefinierten Header hinzugefügt.',
            'header-key'    => 'Header',
            'header-value'  => 'Wert',
        ],
        'create-success' => 'Webhook erfolgreich erstellt',
        'update-success' => 'Webhook erfolgreich aktualisiert',
        'delete-success' => 'Webhook erfolgreich gelöscht',
        'delete-failed'  => 'Löschen des Webhooks fehlgeschlagen',
        'validation'     => [
            'unsafe-url' => 'Die URL verweist auf eine private, Loopback- oder interne Adresse und ist nicht erlaubt.',
            'scheme'     => 'Die URL muss mit http:// oder https:// beginnen.',
        ],
        'test' => [
            'payload-message'   => 'Unopim Webhook-Testanfrage',
            'connection-failed' => 'Die URL konnte nicht erreicht werden. Bitte überprüfen Sie die URL.',
            'unreachable'       => 'Die URL ist nicht erreichbar (HTTP :code).',
            'reachable'         => 'Die URL ist erreichbar.',
        ],
        'prune' => [
            'disabled' => 'Die Aufbewahrung von Webhook-Protokollen ist deaktiviert; es wurde nichts bereinigt.',
            'done'     => ':count Webhook-Protokoll(e), die älter als :days Tag(e) sind, wurden bereinigt.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Einstellungen',
                    'save'    => 'Speichern',
                    'general' => 'Allgemein',
                    'active'  => [
                        'label' => 'Webhook aktiv',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook-URL',
                        'required'          => 'Eine Webhook-URL ist erforderlich, wenn der Webhook aktiv ist.',
                        'scheme'            => 'Die Webhook-URL muss mit http:// oder https:// beginnen.',
                        'connection_failed' => 'Die Webhook-URL konnte nicht erreicht werden. Bitte überprüfen Sie die URL.',
                        'unreachable'       => 'Die Webhook-URL ist ungültig (HTTP :code).',
                        'unsafe'            => 'Die Webhook-URL verweist auf eine private, Loopback- oder interne Adresse und ist nicht erlaubt.',
                    ],
                    'success'    => 'Webhook-Einstellungen erfolgreich gespeichert',
                    'title'      => 'Webhook-Einstellungen',
                    'logs-title' => 'Protokolle',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Ereignis',
                        'created_at'       => 'Datum/Uhrzeit',
                        'user'             => 'Benutzer',
                        'status'           => 'Status',
                        'success'          => 'Erfolgreich',
                        'failed'           => 'Fehlgeschlagen',
                        'server_error'     => 'Serverfehler',
                        'timeout_or_error' => 'Zeitüberschreitung/Fehler',
                        'delete'           => 'Löschen',
                        'view'             => 'Ansehen',
                    ],
                    'title'          => 'Webhook-Protokolle',
                    'show-title'     => 'Webhook-Protokoll Details',
                    'sent-payload'   => 'Gesendete Nutzdaten',
                    'response'       => 'Antwort',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Für dieses Protokoll wurden keine Nutzdaten aufgezeichnet.',
                    'load-failed'    => 'Protokolldetails konnten nicht geladen werden.',
                    'delete-success' => 'Webhook-Protokolle erfolgreich gelöscht',
                    'delete-failed'  => 'Löschen der Webhook-Protokolle unerwartet fehlgeschlagen',
                    'unauthorized'   => 'Diese Aktion ist nicht autorisiert',
                ],
            ],
        ],
    ],
];
