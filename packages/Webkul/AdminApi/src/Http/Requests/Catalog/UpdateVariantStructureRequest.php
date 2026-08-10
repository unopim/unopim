<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\Product\Services\VariantStructureWriter;

/**
 * Shape validation for a variant structure write; VariantStructureWriter validates it
 * against the family and the stored structure. `levels` and `axes` are optional and
 * always taken from storage — accepted so a GET body round-trips, but a differing
 * restatement is rejected rather than silently ignored.
 */
class UpdateVariantStructureRequest extends VariantStructureRequest
{
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'levels'         => ['sometimes', 'integer', Rule::in([1, 2])],
            'axes'           => ['sometimes', 'array', $this->onlyKeys(VariantStructureWriter::AXIS_LEVELS)],
            'axes.level_1'   => ['sometimes', 'array'],
            'axes.level_1.*' => ['string'],
            'axes.level_2'   => ['sometimes', 'array'],
            'axes.level_2.*' => ['string'],
            'placements'     => ['sometimes', 'array', $this->onlyKeys(VariantStructureWriter::PLACEMENT_LEVELS)],
            'placements.*'   => ['array'],
            'placements.*.*' => ['string'],
        ];
    }
}
