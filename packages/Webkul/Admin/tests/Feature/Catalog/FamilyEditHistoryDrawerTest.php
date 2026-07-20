<?php

use Webkul\Attribute\Models\AttributeFamily;

/*
 * History is a drawer overlay that can be opened on top of any family-edit tab,
 * so both `history` and the tab flag arrive together in the query string. The
 * edit view resolves the active tab as variants > completeness > history, and
 * the controller must resolve it identically — otherwise a history fast-path
 * short-circuits before providing the active tab's data and the view crashes on
 * an undefined variable (e.g. $attributeFamilyId on the completeness tab).
 */

it('renders the completeness tab with the history drawer open', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $this->get(route('admin.catalog.families.edit', [
        'id'           => $family->id,
        'completeness' => 1,
        'history'      => 1,
    ]))->assertOk();
});

it('renders the variants tab with the history drawer open', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $this->get(route('admin.catalog.families.edit', [
        'id'       => $family->id,
        'variants' => 1,
        'history'  => 1,
    ]))->assertOk();
});

it('renders the history drawer alone', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    $this->get(route('admin.catalog.families.edit', [
        'id'      => $family->id,
        'history' => 1,
    ]))->assertOk();
});
