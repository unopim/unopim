<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publication',
            'info'     => 'Niveau de diffusion public pour le contenu publié, par langue.',
            'settings' => [
                'title'      => 'Paramètres de publication',
                'enabled'    => 'Activé',
                'base-url'   => 'URL de base',
                'cache-ttl'  => 'Durée du cache (secondes)',
                'rate-limit' => 'Limite de débit (requêtes/minute)',
                'indexable'  => 'Autoriser l\'indexation par les moteurs de recherche',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Brouillon',
            'published' => 'Publié',
            'withdrawn' => 'Retiré',
            'redacted'  => 'Caviardé',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'Passeport introuvable.',
        ],
        '429' => [
            'heading' => 'Trop de requêtes. Veuillez réessayer sous peu.',
        ],
        'withdrawn' => [
            'heading' => 'Ce passeport n\'est plus disponible.',
        ],
    ],
];
