<?php

return [
    'models' => [
        /*
        |----------------------------------------------------------------------
        | Auto-selected model count
        |----------------------------------------------------------------------
        |
        | How many recommended models are pre-selected after a fetch on the
        | "Add AI Platform" form. The rest stay available for manual selection.
        */

        'auto_select_limit' => (int) env('MAGIC_AI_AUTO_SELECT_LIMIT', 5),

        /*
        |----------------------------------------------------------------------
        | Allowed models
        |----------------------------------------------------------------------
        |
        | Comma-delimited model IDs. When set, a fetch is intersected with this
        | list and nothing else is offered. Empty means every fetched model.
        */

        'allowed' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MAGIC_AI_ALLOWED_MODELS', ''))
        ))),
    ],
];
