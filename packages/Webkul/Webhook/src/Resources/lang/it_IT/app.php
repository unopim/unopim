<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Si prega di abilitare il Webhook dalle impostazioni',
        'success'       => 'I dati del prodotto sono stati inviati al Webhook con successo',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Impostazioni',
            'update' => 'Aggiorna impostazioni',
        ],
        'logs' => [
            'index'       => 'Registri',
            'delete'      => 'Elimina',
            'mass-delete' => 'Eliminazione di massa',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Impostazioni',
                    'title'   => 'Impostazioni Webhook',
                    'save'    => 'Salva',
                    'general' => 'Generale',
                    'active'  => [
                        'label' => 'Webhook attivo',
                    ],
                    'webhook_url' => [
                        'label' => 'URL del Webhook',
                    ],
                    'success'    => 'Impostazioni Webhook salvate con successo',
                    'logs-title' => 'Registri',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Data/Ora',
                        'user'       => 'Utente',
                        'status'     => 'Stato',
                        'success'    => 'Successo',
                        'failed'     => 'Fallito',
                        'delete'     => 'Elimina',
                    ],
                    'title'          => 'Registri Webhook',
                    'delete-success' => 'Registri Webhook eliminati con successo',
                    'delete-failed'  => 'L\'eliminazione dei registri Webhook è fallita inaspettatamente',
                ],
            ],
        ],
    ],
];
