<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class PublishPassportApiRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'channel_id'   => ['required', 'integer', 'exists:channels,id'],
            'locale_ids'   => ['required', 'array', 'min:1'],
            'locale_ids.*' => ['integer', 'exists:locales,id'],
        ];
    }
}
