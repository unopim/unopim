<?php

namespace Webkul\Attribute\Support;

use Illuminate\Support\Facades\Event;

/**
 * Collects the extra validation rules packages contribute for an attribute
 * payload.
 *
 * Every write path — the admin form, the REST create and the REST update —
 * runs its rules through the same event, so a package that adds a field to the
 * attribute form validates it everywhere by registering one listener, and core
 * never names that field.
 */
class AttributeValidationRules
{
    /**
     * Listeners receive the submitted data and the id of the attribute being
     * updated (null while creating), and return rules keyed by field name.
     */
    public const EVENT = 'catalog.attribute.validate.rules';

    /**
     * @param  array<string, mixed>  $rules  the caller's own rules
     * @param  array<string, mixed>  $data  the submitted payload
     * @param  int|null  $attributeId  the attribute being updated, or null on create
     * @return array<string, mixed>
     */
    public static function extend(array $rules, array $data, ?int $attributeId = null): array
    {
        foreach (Event::dispatch(self::EVENT, [$data, $attributeId]) as $contributed) {
            if (is_array($contributed)) {
                $rules = array_merge($rules, $contributed);
            }
        }

        return $rules;
    }
}
