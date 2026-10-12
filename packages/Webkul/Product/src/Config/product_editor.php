<?php

return [
    /**
     * Families with more attributes than this render one group at a time.
     * Below it the edit page renders every group, as it always has.
     */
    'lazy_group_threshold' => (int) env('UNOPIM_PRODUCT_EDITOR_LAZY_GROUP_THRESHOLD', 200),

    'groups_per_page' => (int) env('UNOPIM_PRODUCT_EDITOR_GROUPS_PER_PAGE', 20),
];
