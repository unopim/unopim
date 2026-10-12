<?php

namespace Webkul\Completeness\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\LazyCollection;
use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;
use Webkul\Completeness\DataGrids\AttributeCompletenessDataGrid;

class MassUpdateCompletenessRequest extends FormRequest
{
    use SelectsAllMatching;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->selectionRules(), [
            'familyId'             => ['required', 'integer', 'exists:attribute_families,id'],
            'channel_requirements' => ['nullable', 'string'],
        ]);
    }

    /**
     * The completeness grid is scoped to one family, so the family is bound before its filters are replayed.
     *
     * @return LazyCollection<int, mixed>
     */
    public function selectedAttributeIds(): LazyCollection
    {
        if (! $this->selectsAllMatching()) {
            return $this->selectedIds(AttributeCompletenessDataGrid::class);
        }

        return app(AttributeCompletenessDataGrid::class)
            ->setAttributeFamilyId($this->integer('familyId'))
            ->getMatchingIds();
    }
}
