<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publication',
            'info'     => 'Niveau de diffusion public pour le contenu publié, par langue.',
            'settings' => [
                'title'                  => 'Paramètres de publication',
                'enabled'                => 'Activé',
                'enabled-hint'           => 'Interrupteur principal du niveau de service public. Lorsqu\'il est désactivé, chaque URL publique de passeport renvoie une erreur 404 et le menu des passeports est masqué.',
                'base-url'               => 'URL de base',
                'base-url-hint'          => 'Adresse publique où les passeports sont servis, utilisée pour générer les codes QR et les liens partageables. Laissez vide pour utiliser le domaine propre de ce site.',
                'base-url-placeholder'   => 'https://dpp.example.com',
                'cache-ttl'              => 'Durée du cache (secondes)',
                'cache-ttl-hint'         => 'Durée de mise en cache d\'un passeport public rendu avant sa reconstruction. Des valeurs plus élevées réduisent la charge ; des valeurs plus faibles reflètent les modifications plus rapidement.',
                'cache-ttl-placeholder'  => '3600',
                'rate-limit'             => 'Limite de débit (requêtes/minute)',
                'rate-limit-hint'        => 'Nombre maximal de requêtes de passeports publics autorisées par minute depuis un même visiteur avant qu\'il ne soit limité.',
                'rate-limit-placeholder' => '60',
                'indexable'              => 'Autoriser l\'indexation par les moteurs de recherche',
                'indexable-hint'         => 'Autoriser les moteurs de recherche à indexer les pages publiques des passeports. Désactivez pour que les passeports restent accessibles par lien mais masqués dans les résultats de recherche.',
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
        'product-delete-blocked' => 'Ce produit ne peut pas être supprimé tant qu\'il a des passeports publiés. Retirez-les d\'abord.',
        'channel-delete-blocked' => 'Ce canal ne peut pas être supprimé tant qu\'il a des passeports publiés. Retirez-les d\'abord.',
    ],
    'public' => [
        '404' => [
            'heading' => 'Passeport introuvable.',
            'notice'  => 'Ce passeport produit n\'est pas disponible. Il n\'est peut-être pas encore publié ou le lien est incorrect.',
        ],
        '429' => [
            'heading' => 'Trop de requêtes.',
            'notice'  => 'Vous avez effectué trop de requêtes. Veuillez patienter un instant et réessayer.',
        ],
        'withdrawn' => [
            'heading' => 'Ce passeport n\'est plus disponible.',
            'notice'  => 'Cet enregistrement est conservé à des fins de transparence, mais n\'est plus activement maintenu.',
        ],
    ],
];
