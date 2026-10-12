<?php

use Illuminate\Support\Str;

/**
 * The searchable menu is teleported and fixed-positioned, so nothing clips it
 * back into view: it opened below the toggle unconditionally and ran past the
 * fold. A short list never reaches `max-h-72`, so there was not even an inner
 * scrollbar to reach the hidden entries with.
 *
 * @see SelectDropdownOpenDirectionTest — the same rule, as vue-multiselect applies it
 */
function searchableMenuSource(): string
{
    return file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/form/searchable-menu.blade.php')
    );
}

function searchableMenuPosition(): string
{
    return Str::between(searchableMenuSource(), 'position() {', 'select(id) {');
}

it('flips above only when the panel does not fit below and more room sits above', function () {
    expect(searchableMenuPosition())
        ->toContain('const above = height > spaceBelow && spaceAbove > spaceBelow;');
});

it('measures the rendered panel rather than assuming a height', function () {
    expect(searchableMenuPosition())
        ->toContain('this.$refs.panel?.offsetHeight')
        ->and(searchableMenuSource())->toContain('ref="panel"');
});

it('clamps the top into the viewport the way it already clamps the left', function () {
    $position = searchableMenuPosition();

    expect($position)
        ->toContain('Math.max(margin, Math.min(top, window.innerHeight - height - margin))')
        ->and($position)->toContain('Math.max(margin, Math.min(rect.right - width, window.innerWidth - width - margin))');
});

it('no longer pins the panel underneath the toggle', function () {
    expect(searchableMenuPosition())->not->toContain('top: (rect.bottom + 4)');
});

it('keeps repositioning while the page scrolls under an open menu', function () {
    $source = searchableMenuSource();

    expect($source)->toContain('this.onViewportChange = () => this.isOpen && this.position();')
        ->and($source)->toContain("window.addEventListener('scroll', this.onViewportChange, true);");
});
