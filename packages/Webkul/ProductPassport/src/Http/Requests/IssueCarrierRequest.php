<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueCarrierRequest extends FormRequest
{
    /**
     * Issuing a carrier commits a release to print, so it sits with publish rights, not view rights.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.publish');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
