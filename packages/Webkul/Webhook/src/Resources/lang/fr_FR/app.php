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
            'index'  => 'Webhook',
            'create' => 'Créer',
            'edit'   => 'Modifier',
            'delete' => 'Supprimer',
        ],
        'settings' => [
            'index'  => 'Paramètres',
            'update' => 'Mettre à jour les paramètres',
        ],
        'logs' => [
            'index'       => 'Journaux',
            'view'        => 'Voir',
            'delete'      => 'Supprimer',
            'mass-delete' => 'Suppression en masse',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produit créé',
            'updated' => 'Produit mis à jour',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Créer un Webhook',
            'logs-btn'     => 'Journaux',
            'back-btn'     => 'Retour aux Webhooks',
            'default-name' => 'Par défaut',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Nom',
                'url'        => 'URL',
                'events'     => 'Événements',
                'status'     => 'Statut',
                'active'     => 'Actif',
                'inactive'   => 'Inactif',
                'created_at' => 'Créé le',
                'edit'       => 'Modifier',
                'delete'     => 'Supprimer',
            ],
        ],
        'create' => [
            'title'    => 'Créer un Webhook',
            'cancel'   => 'Annuler',
            'save-btn' => 'Enregistrer',
        ],
        'edit' => [
            'title'    => 'Modifier le Webhook',
            'cancel'   => 'Annuler',
            'save-btn' => 'Enregistrer',
        ],
        'form' => [
            'general'       => 'Général',
            'name'          => 'Nom',
            'url'           => 'URL',
            'events'        => 'Événements',
            'select-events' => 'Sélectionner des événements',
            'secret'        => 'Secret de signature',
            'secret-set'    => 'Un secret est déjà défini',
            'secret-hint'   => 'Utilisé pour signer chaque charge utile avec une signature HMAC SHA-256. Laissez vide pour conserver le secret actuel.',
            'settings'      => 'Paramètres',
            'active'        => 'Actif',
            'test'          => 'Tester la connexion',
            'test-hint'     => 'Envoyer une requête de test à l\'URL ci-dessus.',
            'test-btn'      => 'Envoyer le test',
            'test-no-url'   => 'Veuillez d\'abord saisir une URL.',
            'test-failed'   => 'La requête de test a échoué.',
            'headers'       => 'En-têtes personnalisés',
            'add-header'    => 'Ajouter un en-tête',
            'no-headers'    => 'Aucun en-tête personnalisé ajouté.',
            'header-key'    => 'En-tête',
            'header-value'  => 'Valeur',
        ],
        'create-success' => 'Webhook créé avec succès',
        'update-success' => 'Webhook mis à jour avec succès',
        'delete-success' => 'Webhook supprimé avec succès',
        'delete-failed'  => 'Échec de la suppression du Webhook',
        'validation'     => [
            'unsafe-url' => 'L\'URL pointe vers une adresse privée, loopback ou interne et n\'est pas autorisée.',
            'scheme'     => 'L\'URL doit commencer par http:// ou https://.',
        ],
        'test' => [
            'payload-message'   => 'Requête de test du webhook Unopim',
            'connection-failed' => 'L\'URL n\'a pas pu être atteinte. Veuillez vérifier l\'URL.',
            'unreachable'       => 'L\'URL n\'est pas accessible (HTTP :code).',
            'reachable'         => 'L\'URL est accessible.',
        ],
        'prune' => [
            'disabled' => 'La conservation des journaux de webhook est désactivée ; rien n\'a été supprimé.',
            'done'     => ':count journal(aux) de webhook de plus de :days jour(s) supprimé(s).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Paramètres',
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
                    'title'      => 'Paramètres du Webhook',
                    'logs-title' => 'Journaux',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Événement',
                        'created_at'       => 'Date/Heure',
                        'user'             => 'Utilisateur',
                        'status'           => 'Statut',
                        'success'          => 'Succès',
                        'failed'           => 'Échoué',
                        'server_error'     => 'Erreur du serveur',
                        'timeout_or_error' => 'Délai dépassé/Erreur',
                        'delete'           => 'Supprimer',
                        'view'             => 'Voir',
                    ],
                    'title'          => 'Journaux du Webhook',
                    'show-title'     => 'Détails du journal Webhook',
                    'sent-payload'   => 'Charge utile envoyée',
                    'response'       => 'Réponse',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Aucune charge utile enregistrée pour ce journal.',
                    'load-failed'    => 'Échec du chargement des détails du journal.',
                    'delete-success' => 'Journaux du Webhook supprimés avec succès',
                    'delete-failed'  => 'La suppression des journaux du Webhook a échoué de manière inattendue',
                    'unauthorized'   => 'Cette action n\'est pas autorisée',
                ],
            ],
        ],
    ],
];
