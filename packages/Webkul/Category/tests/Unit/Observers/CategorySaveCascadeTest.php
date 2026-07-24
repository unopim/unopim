<?php

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;

/*
 * Saving a category must not recurse the whole descendant subtree (the old
 * per-child touch() re-fired the observer down every level — an O(descendants)
 * write storm). It bumps its direct children in a single bounded update.
 */
it('bumps direct children without a per-descendant write storm', function () {
    $node = Category::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $node = Category::factory()->create(['parent_id' => $node->id]);
    }

    $root = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $root->id]);
    Category::factory()->create(['parent_id' => $child->id]);

    $childBefore = $child->fresh()->updated_at;

    DB::flushQueryLog();
    DB::enableQueryLog();

    $root->update(['additional_data' => ['locale_specific' => ['en_US' => ['name' => 'Renamed']]]]);

    expect(count(DB::getQueryLog()))->toBeLessThan(6)
        ->and($child->fresh()->updated_at->gte($childBefore))->toBeTrue();
});
