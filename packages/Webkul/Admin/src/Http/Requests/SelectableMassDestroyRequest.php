<?php

namespace Webkul\Admin\Http\Requests;

use Webkul\Admin\Http\Requests\Concerns\SelectsAllMatching;

class SelectableMassDestroyRequest extends MassDestroyRequest
{
    use SelectsAllMatching;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->selectionRules());
    }
}
