<?php

namespace Webkul\Completeness\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;

/**
 * Completeness settings are family-scoped, so a setting for an attribute the family does not
 * hold could never be satisfied and would pull every product's score down.
 */
class AttributeInFamily implements ValidationRule
{
    public function __construct(private readonly int $familyId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ids = is_array($value) ? $value : [$value];

        $outside = resolve(AttributeFamilyRepository::class)->attributeIdsOutsideFamily($this->familyId, $ids);

        if ($outside !== []) {
            $fail('validation.exists')->translate(['attribute' => $attribute]);
        }
    }
}
