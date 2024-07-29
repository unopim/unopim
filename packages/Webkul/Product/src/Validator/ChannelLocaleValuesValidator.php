<?php

namespace Webkul\Product\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Rule\AttributeValueRule;
use Webkul\Product\Validator\Rule\ChannelLocalesRule;

class ChannelLocaleValuesValidator
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
        array $options = [],
        ?array $channelsAndLocales = null,
        ?string $id = null
    ): void {
        if ($channelsAndLocales === null) {
            $channels = $this->channelRepository->all();

            foreach ($channels as $channel) {
                $channelsAndLocales[$channel->code] = $channel->locales->pluck('code')->toArray();
            }
        }

        $rules = $this->generateRules($channelsAndLocales, $data, $id);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(array $channelsAndLocales = [], array $data = [], ?string $productId = null): array
    {
        $rules = [
            AbstractType::CHANNEL_LOCALE_VALUES_KEY.'.*'     => new ChannelLocalesRule($channelsAndLocales),
            AbstractType::CHANNEL_LOCALE_VALUES_KEY.'.*.*.*' => new AttributeValueRule(attributeService: $this->attributeService, isChannelBased: true, isLocaleBased: true, productId: $productId),
        ];

        return $rules;
    }
}
