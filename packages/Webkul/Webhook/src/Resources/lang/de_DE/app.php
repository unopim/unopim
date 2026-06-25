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
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Einstellungen',
            'update' => 'Einstellungen aktualisieren',
        ],
        'logs' => [
            'index'       => 'Protokolle',
            'view'        => 'View',
            'delete'      => 'Löschen',
            'mass-delete' => 'Massenlöschung',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Einstellungen',
                    'title'   => 'Webhook-Einstellungen',
                    'save'    => 'Speichern',
                    'general' => 'Allgemein',
                    'active'  => [
                        'label' => 'Webhook aktiv',
                    ],
                    'webhook_url' => [
                        'label' => 'Webhook-URL',
                    ],
                    'success'    => 'Webhook-Einstellungen erfolgreich gespeichert',
                    'logs-title' => 'Protokolle',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Datum/Uhrzeit',
                        'user'       => 'Benutzer',
                        'status'     => 'Status',
                        'success'    => 'Erfolgreich',
                        'failed'     => 'Fehlgeschlagen',
                        'delete'     => 'Löschen',
                        'view'       => 'View',
                    ],
                    'title'          => 'Webhook-Protokolle',
                    'show-title'     => 'Webhook-Protokoll Details',
                    'sent-payload'   => 'Gesendete Nutzdaten',
                    'response'       => 'Antwort',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Für dieses Protokoll wurden keine Nutzdaten aufgezeichnet.',
                    'delete-success' => 'Webhook-Protokolle erfolgreich gelöscht',
                    'delete-failed'  => 'Löschen der Webhook-Protokolle unerwartet fehlgeschlagen',
                ],
            ],
        ],
    ],
];
