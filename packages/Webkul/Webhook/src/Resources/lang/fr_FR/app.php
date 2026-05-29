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
                        'label'             => 'URL du Webhook',
                        'required'          => 'Une URL de Webhook est requise lorsque le Webhook est actif.',
                        'scheme'            => 'L\'URL du Webhook doit commencer par http:// ou https://.',
                        'connection_failed' => 'L\'URL du Webhook n\'a pas pu être atteinte. Veuillez vérifier l\'URL.',
                        'unreachable'       => 'L\'URL du Webhook n\'est pas valide (HTTP :code).',
                        'unsafe'            => 'L\'URL du webhook pointe vers une adresse privée, loopback ou interne et n\'est pas autorisée.',
                    ],
                    'success'    => 'Paramètres du Webhook enregistrés avec succès',
                    'logs-title' => 'Journaux',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Date/Heure',
                        'user'             => 'Utilisateur',
                        'status'           => 'Statut',
                        'success'          => 'Succès',
                        'failed'           => 'Échoué',
                        'server_error'     => 'Erreur du serveur',
                        'timeout_or_error' => 'Délai dépassé/Erreur',
                        'delete'           => 'Supprimer',
                    ],
                    'title'          => 'Journaux du Webhook',
                    'delete-success' => 'Journaux du Webhook supprimés avec succès',
                    'delete-failed'  => 'La suppression des journaux du Webhook a échoué de manière inattendue',
                ],
            ],
        ],
    ],
];
