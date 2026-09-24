<?php

return [
    'managed' => [
        'provider' => env('MAGIC_AI_MANAGED_PROVIDER', 'concentrate'),

        'label' => env('MAGIC_AI_MANAGED_LABEL', 'Concentrate AI'),

        'api_url' => env('MAGIC_AI_MANAGED_API_URL'),

        'models' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('MAGIC_AI_MANAGED_MODELS', ''))
        ))),
    ],
];
