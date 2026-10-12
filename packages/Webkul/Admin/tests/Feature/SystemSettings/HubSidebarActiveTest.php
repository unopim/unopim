<?php

beforeEach(fn () => $this->loginAsAdmin());

it('activates the parent sidebar group on a system settings hub sub-page', function () {
    // Appearance is a hub sub-page whose URL prefix-matches no sidebar item.
    // Its parent group (Configuration) must still resolve as active so the
    // submenu expands and the group highlights when the sidebar is not collapsed.
    $response = $this->get(route('admin.settings.appearance.index'))
        ->assertOk();

    // Exactly one top-level menu row is marked active; before the fix every row
    // stayed inactive on this route.
    $response->assertSee('group/item active', false);

    // The active group's submenu is force-shown (`!grid`) instead of hover-only.
    $response->assertSee('!grid', false);
});

it('renders a breadcrumb trail of the active menu ancestors on a hub sub-page', function () {
    $response = $this->get(route('admin.settings.appearance.index'))
        ->assertOk();

    // Ancestor crumbs from the active menu chain, plus the page title as the leaf.
    $response
        ->assertSee(trans('admin::app.components.layouts.sidebar.configure'))
        ->assertSee(trans('admin::app.components.layouts.sidebar.system-settings'))
        ->assertSee(trans('admin::app.settings.appearance.title'))
        ->assertSee(trans('admin::app.components.layouts.breadcrumbs.label'), false);
});

it('keeps the current page as the breadcrumb leaf on a hub fields sub-page', function () {
    // A fields row (e.g. system.email) lives under the hub URL, so the deepest
    // active crumb is only a prefix of the current URL — the page itself must
    // still appear as the leaf, not be collapsed into "System Settings".
    $this->get(route('admin.settings.system.edit', 'system.email'))
        ->assertOk()
        ->assertSee(trans('admin::app.components.layouts.sidebar.system-settings'))
        ->assertSee(trans('admin::app.settings.system-settings.email.title'));
});
