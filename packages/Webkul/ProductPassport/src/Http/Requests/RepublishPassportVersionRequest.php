<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepublishPassportVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.publish');
    }

    /**
     * The source version must belong to the route publication and still hold a
     * payload (a redacted version's payload is erased), scoping republish to this
     * publication's own immutable history.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'version_id' => [
                'required',
                'integer',
                Rule::exists('publication_versions', 'id')
                    ->where('publication_id', $this->route('publication')->id)
                    ->whereNull('redacted_at'),
            ],
        ];
    }
}
