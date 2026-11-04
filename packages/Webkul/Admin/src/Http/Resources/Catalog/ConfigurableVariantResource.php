<?php

namespace Webkul\Admin\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ConfigurableVariantResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  array<int, string>  $superAttributeCodes
     */
    public function __construct($resource, protected array $superAttributeCodes = [])
    {
        parent::__construct($resource);
    }

    /**
     * One variant row as the configurable edit page's variants panel needs it:
     * the SKU, the super attribute option codes and the display image.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $image = $this->getProductDisplayImage();

        return [
            'id'     => $this->id,
            'sku'    => $this->sku,
            'values' => [
                'common' => Arr::only($this->values['common'] ?? [], ['sku', ...$this->superAttributeCodes]),
            ],
            'image'  => $image ? Storage::url($image) : null,
        ];
    }
}
