<?php

use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\Publication\Enums\PublicationStatus;

/**
 * The toolbar renders a mass action's choices from `options` as a flat list and
 * gates the submenu on `options.length`. Wrapping them in a `type`/`params`
 * envelope leaves that length undefined, so the submenu never renders, the plain
 * item fires the action with no option, and `value` reaches the request null.
 */
function passportMassTransition(): array
{
    test()->loginWithPermissions('all');

    $grid = resolve(PublicationDataGrid::class);
    $grid->prepareMassActions();

    $action = collect($grid->getMassActions())
        ->first(fn ($massAction): bool => str_contains(
            $massAction->url ?? '',
            route('admin.catalog.passports.mass_transition')
        ));

    expect($action)->not->toBeNull();

    return (array) $action->options;
}

it('offers the transition choices as a list the toolbar can count', function (): void {
    $options = passportMassTransition();

    expect($options)->toBeArray()
        ->and($options)->not->toBeEmpty()
        ->and(array_keys($options))->toBe(range(0, count($options) - 1));
});

it('gives every choice the label and value the toolbar posts', function (): void {
    $values = [];

    foreach (passportMassTransition() as $option) {
        $option = (array) $option;

        expect($option)->toHaveKeys(['label', 'value']);

        $values[] = $option['value'];
    }

    expect($values)->toEqualCanonicalizing([
        PublicationStatus::Withdrawn->value,
        PublicationStatus::Published->value,
    ]);
});
