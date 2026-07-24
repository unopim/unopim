<?php

return [
    /**
     * Nested under the canonical hyphenated `catalog.families.variant-structures`
     * key (defined in Admin acl) so the tree shows a single node, not a duplicate.
     * Routeless: the AI-suggestion toggle is enforced in `SuggestionManager`
     * against `config('suggesters.*.acl')`, not by a route.
     */
    [
        'key'   => 'catalog.families.variant-structures.ai_suggest',
        'name'  => 'product::app.acl.ai-suggest',
        'route' => null,
        'sort'  => 3,
    ],
];
