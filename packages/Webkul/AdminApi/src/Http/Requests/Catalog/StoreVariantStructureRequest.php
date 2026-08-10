<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Code;
use Webkul\Product\Services\VariantStructureWriter;

/**
 * Shape validation for creating a variant structure.
 *
 * Creation is the only moment `levels` and `axes` may be stated, so both are required
 * here where the update verbs keep them optional and immutable. Whether the shape is
 * legal for the family is settled by VariantStructureWriter.
 */
class StoreVariantStructureRequest extends VariantStructureRequest
{
    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', new Code],
            'name'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'levels'         => ['required', 'integer', Rule::in([1, 2])],
            'axes'           => ['required', 'array', $this->onlyKeys(VariantStructureWriter::AXIS_LEVELS)],
            'axes.level_1'   => ['required', 'array'],
            'axes.level_1.*' => ['string'],
            'axes.level_2'   => ['sometimes', 'array'],
            'axes.level_2.*' => ['string'],
            'placements'     => ['sometimes', 'array', $this->onlyKeys(VariantStructureWriter::PLACEMENT_LEVELS)],
            'placements.*'   => ['array'],
            'placements.*.*' => ['string'],
        ];
    }
}
