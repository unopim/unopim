<?php

use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\Attribute\Services\Normalizers\DefaultNormalizer;
use Webkul\Attribute\Services\Normalizers\OptionNormalizer;
use Webkul\Attribute\Services\Normalizers\PriceNormalizer;

beforeEach(function () {
    $this->factory = app(AttributeNormalizerFactory::class);
});

describe('AttributeNormalizerFactory - getNormalizer', function () {
    it('returns PriceNormalizer for price type', function () {
        $normalizer = $this->factory->getNormalizer('price');

        expect($normalizer)->toBeInstanceOf(PriceNormalizer::class);
    });

    it('returns OptionNormalizer for select type', function () {
        $normalizer = $this->factory->getNormalizer('select');

        expect($normalizer)->toBeInstanceOf(OptionNormalizer::class);
    });

    it('returns OptionNormalizer for multiselect type', function () {
        $normalizer = $this->factory->getNormalizer('multiselect');

        expect($normalizer)->toBeInstanceOf(OptionNormalizer::class);
    });

    it('returns OptionNormalizer for checkbox type', function () {
        $normalizer = $this->factory->getNormalizer('checkbox');

        expect($normalizer)->toBeInstanceOf(OptionNormalizer::class);
    });

    it('returns DefaultNormalizer for text type as fallback', function () {
        $normalizer = $this->factory->getNormalizer('text');

        expect($normalizer)->toBeInstanceOf(DefaultNormalizer::class);
    });

    it('returns DefaultNormalizer for unknown type as fallback', function () {
        $normalizer = $this->factory->getNormalizer('unknown_type');

        expect($normalizer)->toBeInstanceOf(DefaultNormalizer::class);
    });

    it('returns DefaultNormalizer for textarea type', function () {
        $normalizer = $this->factory->getNormalizer('textarea');

        expect($normalizer)->toBeInstanceOf(DefaultNormalizer::class);
    });

    it('returns DefaultNormalizer for boolean type', function () {
        $normalizer = $this->factory->getNormalizer('boolean');

        expect($normalizer)->toBeInstanceOf(DefaultNormalizer::class);
    });

    it('caches normalizer instances for same type', function () {
        $first = $this->factory->getNormalizer('price');
        $second = $this->factory->getNormalizer('price');

        expect($first)->toBe($second);
    });

    it('returns different instances for different types', function () {
        $priceNormalizer = $this->factory->getNormalizer('price');
        $optionNormalizer = $this->factory->getNormalizer('select');
        $defaultNormalizer = $this->factory->getNormalizer('text');

        expect($priceNormalizer)->not->toBe($optionNormalizer)
            ->and($optionNormalizer)->not->toBe($defaultNormalizer)
            ->and($priceNormalizer)->not->toBe($defaultNormalizer);
    });
});
