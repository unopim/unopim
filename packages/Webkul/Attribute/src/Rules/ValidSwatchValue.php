<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Attribute\Repositories\AttributeRepository;

class ValidSwatchValue implements ValidationRule
{
    protected int $attributeId;

    protected AttributeRepository $attributeRepository;

    public function __construct(int $attributeId)
    {
        $this->attributeId = $attributeId;
        $this->attributeRepository = app(AttributeRepository::class);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $attr = $this->attributeRepository->find($this->attributeId);

        $isValid = in_array($attr?->type, ['select', 'multiselect'], true)
            && in_array($attr?->swatch_type, ['color', 'image'], true);

        if (! $isValid) {
            $fail(trans('admin::app.catalog.attributes.create.invalid-swatch-type', [
                'attribute'   => $attribute,
                'type'        => $attr?->type,
                'swatch_type' => $attr?->swatch_type ?? 'none',
            ]));
        }
    }
}
