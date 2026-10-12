<?php

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;

/*
 * Saving a category bumps only its DIRECT children in one bounded update. The old
 * per-child touch() re-fired the observer down every level (an O(descendants)
 * write storm) — so a deep descendant must be left untouched, proving no recursion.
 */
it('bumps direct children but not deep descendants, in a bounded update', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $root->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child->id]);

    Category::whereIn('id', [$child->id, $grandchild->id])->update(['updated_at' => now()->subMinutes(5)]);

    $childBefore = $child->fresh()->updated_at;
    $grandchildBefore = $grandchild->fresh()->updated_at;

    DB::flushQueryLog();
    DB::enableQueryLog();

    $root->update(['additional_data' => ['locale_specific' => ['en_US' => ['name' => 'Renamed']]]]);

    expect(count(DB::getQueryLog()))->toBeLessThan(6)
        ->and($child->fresh()->updated_at->gt($childBefore))->toBeTrue()
        ->and($grandchild->fresh()->updated_at->equalTo($grandchildBefore))->toBeTrue();
});
