<?php

namespace Webkul\Attribute\Support;

use Illuminate\Support\Facades\Event;

/**
 * Asks whether anything objects to an attribute being deleted.
 *
 * Core's own guards — system attributes, super attributes — stay where they
 * are; this adds a place for a package to refuse a deletion it would be broken
 * by, and to say why in the user's language. A listener returns a translated
 * message to block, or null to stay out of the way.
 */
class AttributeDeletionGuard
{
    public const EVENT = 'catalog.attribute.delete.restrict';

    /**
     * The first reason given for refusing to delete `$attribute`, or null when
     * nothing objects.
     */
    public static function reason(object $attribute): ?string
    {
        foreach (Event::dispatch(self::EVENT, [$attribute]) as $objection) {
            if (is_string($objection) && $objection !== '') {
                return $objection;
            }
        }

        return null;
    }
}
