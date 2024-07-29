<?php

namespace Webkul\Product\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Rule\AttributeValueRule;
use Webkul\Product\Validator\Rule\KeyExistsRule;

class ChannelValuesValidator
{
    /**
     * create channelValuesValidator object
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected AttributeService $attributeService
    ) {}

    /**
     * Validates the channel based product values data according to attribute value rules
     *
     * @throws ValidationException
     */
    public function validate(mixed $data, array $options = [], ?array $channels = null, ?string $id = null): void
    {
        $channels ??= $this->channelRepository->all()->pluck('code')->toArray();

        $rules = $this->generateRules($channels, $data, $id);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(array $channels = [], array $data = [], ?string $productId = null)
    {
        $rules = [
            AbstractType::CHANNEL_VALUES_KEY.'.*'   => [new KeyExistsRule($channels, AbstractType::CHANNEL_VALUES_KEY.'.'), 'array'],
            AbstractType::CHANNEL_VALUES_KEY.'.*.*' => new AttributeValueRule(attributeService: $this->attributeService, isChannelBased: true, isLocaleBased: false, productId: $productId),
        ];

        return $rules;
    }
}
