<?php

namespace Webkul\Product\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\Rule\AttributeValueRule;
use Webkul\Product\Validator\Rule\KeyExistsRule;

class LocaleValuesValidator
{
    /**
     * create localeValuesValidator
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected AttributeService $attributeService
    ) {}

    /**
     * Validates the locale wise data and according to attribute value rules
     *
     * @throws ValidationException
     */
    public function validate(mixed $data, array $options = [], ?array $locales = null, ?string $id = null): void
    {
        if ($locales === null) {
            $channels = $this->channelRepository->all();

            foreach ($channels as $channel) {
                $locales += $channel->locales->pluck('code')->toArray();
            }
        }

        $rules = $this->generateRules($locales, $data, $id);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validation rules to be used on the data
     */
    protected function generateRules(array $locales = [], array $data = [], ?string $productId = null)
    {
        $rules = [
            AbstractType::LOCALE_VALUES_KEY.'.*'   => [new KeyExistsRule($locales, AbstractType::LOCALE_VALUES_KEY.'.'), 'array'],
            AbstractType::LOCALE_VALUES_KEY.'.*.*' => new AttributeValueRule(attributeService: $this->attributeService, isChannelBased: false, isLocaleBased: true, productId: $productId),
        ];

        return $rules;
    }
}
