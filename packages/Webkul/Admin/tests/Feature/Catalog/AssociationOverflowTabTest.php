<?php

use Webkul\Product\Models\Product;

/**
 * Types that do not fit the tab strip move into a "More" menu, and the strip
 * deliberately keeps its order so tabs never shuffle under the pointer. That
 * left the menu giving no sign of which type was being edited.
 */
function associationTabMarkup(): string
{
    test()->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    return test()->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();
}

it('tells the overflow menu which type is selected', function () {
    expect(associationTabMarkup())->toContain(':model-value="activeTypeCode"');
});

it('keeps the toggle label as More', function () {
    $markup = associationTabMarkup();

    $chevron = strpos($markup, "toggle.isOpen ? 'icon-chevron-up'");

    expect($chevron)->not->toBeFalse();

    $button = substr($markup, max(0, $chevron - 900), 900);

    expect($button)->toContain(trans('admin::app.catalog.products.edit.links.more-types'))
        ->and($button)->not->toContain('overflowToggleLabel');
});

it('turns the chevron over while the menu is open', function () {
    $markup = associationTabMarkup();

    expect($markup)->toContain("toggle.isOpen ? 'icon-chevron-up' : 'icon-chevron-down'");
});

it('marks the selected row in the menu and keeps every label aligned', function () {
    $markup = associationTabMarkup();

    expect($markup)->toContain('bg-primary-50 font-medium text-primary-700')
        ->and($markup)->toContain("item.id === modelValue || item.checked ? '' : 'opacity-0'");
});

it('reserves the selected column ahead of the badge', function () {
    $markup = associationTabMarkup();

    $marker = strpos($markup, 'shrink-0 icon-done text-lg text-primary-700');
    $badge = strpos($markup, 'v-if="item.badge"');

    expect($marker)->not->toBeFalse()
        ->and($badge)->not->toBeFalse()
        ->and($marker)->toBeLessThan($badge)
        ->and($markup)->not->toContain('v-else-if="item.checked"');
});
