<?php

namespace Webkul\Shopify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CredentialForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shopUrl'     => 'required|unique:wk_shopify_credentials_config',
            'accessToken' => 'required',
            'apiVersion'  => 'required',
        ];
    }
}
