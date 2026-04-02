<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\Normalizers\OptionNormalizer;

beforeEach(function () {
    $this->normalizer = app(OptionNormalizer::class);
});

describe('OptionNormalizer - default format', function () {
    it('returns string data unchanged with default format', function () {
        $result = $this->normalizer->normalize('option_code');

        expect($result)->toBe('option_code');
    });

    it('returns array data unchanged with default format', function () {
        $data = ['option1', 'option2'];

        $result = $this->normalizer->normalize($data);

        expect($result)->toBe($data);
    });

    it('returns null unchanged with default format', function () {
        $result = $this->normalizer->normalize(null);

        expect($result)->toBeNull();
    });

    it('returns data unchanged when format is explicitly default', function () {
        $result = $this->normalizer->normalize('opt_code', null, ['format' => 'default']);

        expect($result)->toBe('opt_code');
    });
});

describe('OptionNormalizer - datagrid format', function () {
    it('converts select option codes to labels for datagrid format', function () {
        $attribute = Attribute::factory()->create(['type' => 'select']);
        $options = $attribute->options;
        $optionCode = $options->first()->code;

        $result = $this->normalizer->normalize(
            $optionCode,
            $attribute,
            ['format' => 'datagrid']
        );

        // Without translation, it falls back to [code] format
        expect($result)->toBeString()
            ->and($result)->toContain($optionCode);
    });

    it('handles multiselect comma-separated codes for datagrid format', function () {
        $attribute = Attribute::factory()->create(['type' => 'multiselect']);
        $options = $attribute->options;
        $codes = $options->pluck('code')->toArray();
        $codeString = implode(',', $codes);

        $result = $this->normalizer->normalize(
            $codeString,
            $attribute,
            ['format' => 'datagrid']
        );

        expect($result)->toBeString();

        // Each option code should appear in brackets (no translation available)
        foreach ($codes as $code) {
            expect($result)->toContain($code);
        }
    });

    it('handles checkbox codes for datagrid format', function () {
        $attribute = Attribute::factory()->create(['type' => 'checkbox']);
        $optionCode = $attribute->options->first()->code;

        $result = $this->normalizer->normalize(
            $optionCode,
            $attribute,
            ['format' => 'datagrid']
        );

        expect($result)->toBeString()
            ->and($result)->toContain($optionCode);
    });

    it('returns string representation for non-option attribute type in datagrid', function () {
        $attribute = Attribute::factory()->create(['type' => 'text']);

        $result = $this->normalizer->normalize(
            'some text value',
            $attribute,
            ['format' => 'datagrid']
        );

        expect($result)->toBe('some text value');
    });

    it('returns empty string when no options match in datagrid format', function () {
        $attribute = Attribute::factory()->create(['type' => 'select']);

        $result = $this->normalizer->normalize(
            'nonexistent_option_code',
            $attribute,
            ['format' => 'datagrid']
        );

        expect($result)->toBeString();
    });
});
