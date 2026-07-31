<?php

namespace Webkul\Resource\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Resource\Support\ResourceRegistry;

class TestForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Pulled from the resource's field schema — single source of truth
     * shared between the frontend field definitions and this FormRequest.
     *
     * @return array
     */
    public function rules()
    {
        return app(ResourceRegistry::class)->get('resource-kit-items')->schema()->rules();
    }
}
