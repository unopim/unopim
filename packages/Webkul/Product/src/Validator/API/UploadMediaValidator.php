<?php

namespace Webkul\Product\Validator\API;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;

class UploadMediaValidator
{
    /**
     * @return self
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected AttributeService $attributeService
    ) {}

    /**
     * Validates the channel and locale based product values data according to attribute value rules
     *
     * @throws ValidationException
     */
    public function validate(
        mixed $data,
        ?string $id = null,
        array $options = []
    ): void {

        $attributeCode = $data['attribute'];
        $productAttribute = $this->attributeService->findAttributeByCode($attributeCode);
        $rules = $this->generateRules($productAttribute);
        $validator = Validator::make($data, $rules);

        if (! $productAttribute) {
            $validator->after(function ($validator) use ($attributeCode) {
                $validator->errors()->add('attribute', trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
            });
        }

        if ($productAttribute && ! in_array($productAttribute->type, [AttributeTypes::FILE_ATTRIBUTE_TYPE, AttributeTypes::IMAGE_ATTRIBUTE_TYPE, AttributeTypes::GALLERY_ATTRIBUTE_TYPE])) {
            $validator->after(function ($validator) use ($attributeCode) {
                $validator->errors()->add('attribute', trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]));
            });
        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules($productAttribute): array
    {
        $rules = [
            'file' => $productAttribute ? $productAttribute->getValidationsOnlyMedia() : [],
        ];

        return $rules;
    }
}
