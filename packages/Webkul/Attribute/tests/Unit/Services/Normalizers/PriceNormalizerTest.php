<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\Normalizers\PriceNormalizer;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;

beforeEach(function () {
    $this->normalizer = app(PriceNormalizer::class);
});

describe('PriceNormalizer - default format', function () {
    it('returns string data unchanged with default format', function () {
        $result = $this->normalizer->normalize('100.00');

        expect($result)->toBe('100.00');
    });

    it('returns array data unchanged with default format', function () {
        $data = ['USD' => '99.99', 'EUR' => '89.99'];

        $result = $this->normalizer->normalize($data);

        expect($result)->toBe($data);
    });

    it('returns null unchanged with default format', function () {
        $result = $this->normalizer->normalize(null);

        expect($result)->toBeNull();
    });

    it('returns data unchanged when format is explicitly default', function () {
        $data = ['USD' => '50.00'];

        $result = $this->normalizer->normalize($data, null, ['format' => 'default']);

        expect($result)->toBe($data);
    });
});

describe('PriceNormalizer - datagrid format', function () {
    it('formats currency array with symbols for datagrid', function () {
        $data = ['USD' => '99.99'];
        $attribute = Attribute::factory()->create(['type' => 'price']);

        $result = $this->normalizer->normalize($data, $attribute, ['format' => 'datagrid']);

        expect($result)->toBeString()
            ->and($result)->toContain('99.99');
    });

    it('formats multiple currencies with comma separation', function () {
        $data = ['USD' => '99.99', 'EUR' => '89.99'];
        $attribute = Attribute::factory()->create(['type' => 'price']);

        $result = $this->normalizer->normalize($data, $attribute, ['format' => 'datagrid']);

        expect($result)->toBeString()
            ->and($result)->toContain('99.99')
            ->and($result)->toContain('89.99')
            ->and($result)->toContain(', ');
    });

    it('returns non-array data unchanged in datagrid format', function () {
        $result = $this->normalizer->normalize('simple_string', null, ['format' => 'datagrid']);

        expect($result)->toBe('simple_string');
    });

    it('filters out currencies not active on the requested channel', function () {
        $data = ['USD' => '90', 'INR' => '7495'];

        $currency = Currency::factory()->create(['code' => 'USD', 'status' => 1]);
        $channel = Channel::factory()->create(['code' => 'filter_test_channel']);
        $channel->currencies()->sync([$currency->id]);

        $attribute = Attribute::factory()->create(['type' => 'price']);

        $result = $this->normalizer->normalize(
            $data,
            $attribute,
            ['format' => 'datagrid', 'channel' => 'filter_test_channel']
        );

        expect($result)->toBeString()
            ->and($result)->toContain('90')
            ->and($result)->not->toContain('7495');
    });

    it('falls back to all currencies when channel context is absent', function () {
        $data = ['USD' => '90', 'INR' => '7495'];
        $attribute = Attribute::factory()->create(['type' => 'price']);

        $result = $this->normalizer->normalize(
            $data,
            $attribute,
            ['format' => 'datagrid']
        );

        expect($result)->toBeString()
            ->and($result)->toContain('90')
            ->and($result)->toContain('7495');
    });
});
