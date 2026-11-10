<?php

namespace Webkul\Attribute\Support;

/**
 * Reads the flags a package may declare alongside its attribute type in the
 * `attribute_types` config.
 *
 * Keeps type-specific behaviour out of core: a package that adds a type whose
 * value is derived rather than entered marks it `'fillable' => false` in the
 * config it merges, and core treats it accordingly without knowing the type.
 */
class AttributeTypeRegistry
{
    /**
     * Attribute types whose values are never written by a user or an import.
     *
     * Such a value is produced from other data at read time, so it cannot be
     * filled in: an importer must discard the incoming column instead of
     * storing it, and a completeness requirement on one could never be
     * satisfied by any action an editor could take.
     *
     * @return array<int, string>
     */
    public static function nonFillableTypes(): array
    {
        $types = config('attribute_types');

        if (! is_array($types)) {
            return [];
        }

        return array_values(array_keys(array_filter(
            $types,
            static fn (mixed $definition): bool => is_array($definition) && ($definition['fillable'] ?? true) === false
        )));
    }

    /**
     * Whether values of the given type can be stored at all.
     */
    public static function isFillable(?string $type): bool
    {
        return ! in_array((string) $type, self::nonFillableTypes(), true);
    }
}
