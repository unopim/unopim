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
        'delete-failed' => 'Veuillez activer le Webhook dans les paramètres',
        'success'       => 'Les données du produit ont été envoyées au Webhook avec succès',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Paramètres',
            'update' => 'Mettre à jour les paramètres',
        ],
        'logs' => [
            'index'       => 'Journaux',
            'view'        => 'View',
            'delete'      => 'Supprimer',
            'mass-delete' => 'Suppression en masse',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Paramètres',
                    'title'   => 'Paramètres du Webhook',
                    'save'    => 'Enregistrer',
                    'general' => 'Général',
                    'active'  => [
                        'label' => 'Webhook actif',
                    ],
                    'webhook_url' => [
                        'label' => 'URL du Webhook',
                    ],
                    'success'    => 'Paramètres du Webhook enregistrés avec succès',
                    'logs-title' => 'Journaux',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Date/Heure',
                        'user'       => 'Utilisateur',
                        'status'     => 'Statut',
                        'success'    => 'Succès',
                        'failed'     => 'Échoué',
                        'delete'     => 'Supprimer',
                        'view'       => 'View',
                    ],
                    'title'          => 'Journaux du Webhook',
                    'show-title'     => 'Détails du journal Webhook',
                    'sent-payload'   => 'Charge utile envoyée',
                    'response'       => 'Réponse',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Aucune charge utile enregistrée pour ce journal.',
                    'delete-success' => 'Journaux du Webhook supprimés avec succès',
                    'delete-failed'  => 'La suppression des journaux du Webhook a échoué de manière inattendue',
                ],
            ],
        ],
    ],
];
