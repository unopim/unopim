<?php

namespace Webkul\Attribute\Services;

use Illuminate\Contracts\Container\Container;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\Normalizers\DefaultNormalizer;
use Webkul\Attribute\Services\Normalizers\OptionNormalizer;
use Webkul\Attribute\Services\Normalizers\PriceNormalizer;

class AttributeNormalizerFactory
{
    protected array $normalizers = [
        Attribute::PRICE_FIELD_TYPE       => PriceNormalizer::class,
        Attribute::SELECT_FIELD_TYPE      => OptionNormalizer::class,
        Attribute::MULTISELECT_FIELD_TYPE => OptionNormalizer::class,
    ];

    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function getNormalizer(string $type): AttributeNormalizerInterface
    {
        if (isset($this->normalizers[$type])) {
            return $this->app->make($this->normalizers[$type]);
        }

        return $this->app->make(DefaultNormalizer::class);
    }
}
