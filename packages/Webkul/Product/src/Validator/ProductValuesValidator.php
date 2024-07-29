<?php

namespace Webkul\Product\Validator;

use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Type\AbstractType;

class ProductValuesValidator
{
    private array $savedRules;

    protected $channelLocaleValuesValidator;

    protected $channelValuesValidator;

    protected $localeValuesValidator;

    protected $sectionsValidator;

    protected $commonValidator;

    protected $categoriesValidator;

    protected $associationsValidator;

    /**
     * @return self
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeService $attributeService
    ) {
        $this->initializeValidators();
    }

    /**
     * Validate different sections data in product values json
     *
     * @throws Illuminate\Validation\ValidationException
     */
    public function validate(mixed $data, array $options = [], ?int $productId = null): void
    {
        $this->sectionsValidator->validate($data, $options);

        $channels = $this->channelRepository->all();

        $channelsAndLocales = [];

        $locales = [];

        foreach ($channels as $channel) {
            $channelLocales = $channel->locales->pluck('code')->toArray();

            $channelsAndLocales[$channel->code] = $channelLocales;

            $locales = array_unique(array_merge($locales, $channelLocales));
        }

        $channelCodes = $channel->pluck('code')->toArray();

        $this->commonValidator->validate(data: $data, id: $productId);

        $this->channelLocaleValuesValidator->validate(data: $data, channelsAndLocales: $channelsAndLocales, id: $productId);

        $this->channelValuesValidator->validate(data: $data, channels: $channelCodes, id: $productId);

        $this->localeValuesValidator->validate(data: $data, locales: $locales, id: $productId);

        $this->categoriesValidator->validate(data: $data[AbstractType::CATEGORY_VALUES_KEY] ?? []);

        $this->associationsValidator->validate(data: $data[AbstractType::ASSOCIATION_VALUES_KEY] ?? []);
    }

    /**
     * Validation rules to be used on the data
     */
    private function initializeValidators(): void
    {
        $this->channelLocaleValuesValidator = new ChannelLocaleValuesValidator($this->channelRepository, $this->attributeService);

        $this->channelValuesValidator = new ChannelValuesValidator($this->channelRepository, $this->attributeService);

        $this->localeValuesValidator = new LocaleValuesValidator($this->channelRepository, $this->attributeService);

        $this->sectionsValidator = new SectionsValidator();

        $this->commonValidator = new CommonValuesValidator($this->attributeService);

        $this->categoriesValidator = new ProductCategoriesValidator();

        $this->associationsValidator = new ProductAssociationsValidator();
    }
}
