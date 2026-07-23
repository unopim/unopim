<?php

use Webkul\Product\Services\VariantPlacementSuggester;

return [
    'variant_placement' => [
        'class'  => VariantPlacementSuggester::class,
        'acl'    => 'catalog.families.variant_structures.ai_suggest',
        'config' => 'general.magic_ai.settings.variant_suggestion_enabled',
        'ai'     => [
            'model'       => null,   // null = the platform default
            'temperature' => 0.2,    // deterministic
            'max_tokens'  => 1500,   // cost cap
        ],
    ],

    // Add more AI-assisted suggesters here (product code, name, data generation, ...).
];
