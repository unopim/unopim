<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Publication\Rules\Gs1CheckDigit;

class PublishPassportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.publish');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel_id'   => ['required', 'integer', 'exists:channels,id'],
            'locale_ids'   => ['required', 'array', 'min:1'],
            'locale_ids.*' => ['integer', 'exists:locales,id'],
            'gtin'         => ['nullable', 'string', new Gs1CheckDigit],
        ];
    }
}
