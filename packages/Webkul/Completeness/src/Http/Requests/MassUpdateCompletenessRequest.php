<?php

namespace Webkul\Completeness\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\LazyCollection;
use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;
use Webkul\Completeness\DataGrids\AttributeCompletenessDataGrid;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;

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
        $rules = array_merge($this->selectionRules(), [
            'familyId'             => ['required', 'integer', 'exists:attribute_families,id'],
            'channel_requirements' => ['nullable', 'string'],
        ]);

        $rules['indices'][] = function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_array($value) || ! $this->filled('familyId')) {
                return;
            }

            $outside = resolve(CompletenessSettingsRepository::class)
                ->attributeIdsOutsideFamily($this->integer('familyId'), $value);

            if ($outside !== []) {
                $fail(trans('validation.exists', ['attribute' => $attribute]));
            }
        };

        return $rules;
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

        return resolve(AttributeCompletenessDataGrid::class)
            ->setAttributeFamilyId($this->integer('familyId'))
            ->getSelectedIds();
    }
}
