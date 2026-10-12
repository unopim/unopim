<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\VueJsSelect\AbstractOptionsController;

class SelectOptionsForm extends FormRequest
{
    /**
     * Hard ceiling on a page of options so a crafted `limit` cannot pull a whole
     * entity table in one request.
     */
    const MAX_PER_PAGE = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entityName' => ['required', 'string', Rule::in(AbstractOptionsController::SUPPORTED_ENTITIES)],
            'page'       => ['nullable', 'integer', 'min:1'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'query'      => ['nullable', 'string', 'max:255'],
            'source'     => ['nullable', 'string', 'max:255'],
        ];
    }
}
