<?php

namespace Webkul\Shopify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\BooleanString;

class ExportMappingForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default_taxable'           => new BooleanString,
            'default_inventoryPolicy'   => new BooleanString,
            'default_inventoryTracked'  => new BooleanString,
            'default_price'             => 'numeric',
            'default_weight'            => 'numeric',
            'default_inventoryQuantity' => 'numeric',
            'default_compareAtPrice'    => 'numeric',
            'default_cost'              => 'numeric',
        ];
    }
}
