<?php

declare(strict_types=1);

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
                        'label'             => 'URL del Webhook',
                        'required'          => 'L\'URL del Webhook è obbligatorio quando il Webhook è attivo.',
                        'scheme'            => 'L\'URL del Webhook deve iniziare con http:// o https://.',
                        'connection_failed' => 'Impossibile raggiungere l\'URL del Webhook. Verifica l\'URL.',
                        'unreachable'       => 'L\'URL del Webhook non è valido (HTTP :code).',
                        'unsafe'            => 'L\'URL del webhook punta a un indirizzo privato, di loopback o interno e non è consentito.',
                    ],
                    'success'    => 'Impostazioni Webhook salvate con successo',
                    'logs-title' => 'Registri',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Data/Ora',
                        'user'             => 'Utente',
                        'status'           => 'Stato',
                        'success'          => 'Successo',
                        'failed'           => 'Fallito',
                        'server_error'     => 'Errore del server',
                        'timeout_or_error' => 'Timeout/Errore',
                        'delete'           => 'Elimina',
                    ],
                    'title'          => 'Registri Webhook',
                    'delete-success' => 'Registri Webhook eliminati con successo',
                    'delete-failed'  => 'L\'eliminazione dei registri Webhook è fallita inaspettatamente',
                ],
            ],
        ],
    ],
];
