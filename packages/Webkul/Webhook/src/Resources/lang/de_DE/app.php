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
                    ],
                    'title'          => 'Webhook-Protokolle',
                    'delete-success' => 'Webhook-Protokolle erfolgreich gelöscht',
                    'delete-failed'  => 'Löschen der Webhook-Protokolle unerwartet fehlgeschlagen',
                ],
            ],
        ],
    ],
];
