<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Webkul\Core\Rules\Code;

class ChannelForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        if (! $this->isUpdating()) {
            $rules['code'] = ['required', 'unique:channels,code', new Code];
        }

        $rules['root_category_id'] = 'required';
        $rules['locales'] = ['required', 'array'];
        $rules['locales.*'] = ['integer', 'exists:locales,id'];
        $rules['currencies'] = ['required', 'array'];
        $rules['currencies.*'] = ['integer', 'exists:currencies,id'];

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = 'nullable';
        }

        return $rules;
    }

    /**
     * Expand the comma separated id lists posted by the multiselect controls.
     *
     * Element rules are matched against the validator's data snapshot, so the list has to be an
     * array before validation starts, otherwise `locales.*` / `currencies.*` never run.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'locales'    => $this->normalizeIdList($this->input('locales')),
            'currencies' => $this->normalizeIdList($this->input('currencies')),
        ]);
    }

    /**
     * Determine whether an existing channel is being edited.
     */
    protected function isUpdating(): bool
    {
        return $this->route('id') !== null;
    }

    /**
     * Turn a scalar or comma separated id list into a clean array of raw ids.
     *
     * Always returns an array so the element rules expand for every payload shape.
     *
     * @return array<int, mixed>
     */
    protected function normalizeIdList(mixed $value): array
    {
        $value = is_string($value) ? explode(',', $value) : Arr::wrap($value);

        $ids = array_map(fn ($id) => is_scalar($id) ? trim((string) $id) : $id, $value);

        return array_values(array_filter($ids, fn ($id) => $id !== '' && $id !== null));
    }
}
