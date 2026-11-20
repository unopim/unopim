<?php

namespace Webkul\Core\Traits;

trait HasCatalogFieldClientValidations
{
    use ResolvesClientValidationRule;

    /**
     * Returns field validation rules
     */
    public function getValidationsField(): string
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required: true';
        }

        if ($this->type === 'file') {
            $validations[] = 'size:'.(core()->getConfigData('catalog.categories.fields.file_attribute_upload_size') ?? '2048');
        }

        if ($this->type === 'image') {
            $retVal = core()->getConfigData('catalog.categories.fields.image_attribute_upload_size') ?? '2048';

            if ($retVal) {
                $validations[] = 'size:'.$retVal.', mimes: ["image/bmp", "image/jpeg", "image/jpg", "image/png", "image/webp"]';
            }
        }

        if ($this->validation) {
            $validations[] = $this->clientValidationRule();
        }

        return '{ '.implode(', ', array_filter($validations)).' }';
    }
}
