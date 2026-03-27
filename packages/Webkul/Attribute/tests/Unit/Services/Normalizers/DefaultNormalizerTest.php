<?php

use Webkul\Attribute\Services\Normalizers\DefaultNormalizer;

beforeEach(function () {
    $this->normalizer = app(DefaultNormalizer::class);
});

describe('DefaultNormalizer', function () {
    it('returns string data unchanged', function () {
        $result = $this->normalizer->normalize('hello world');

        expect($result)->toBe('hello world');
    });

    it('returns array data unchanged', function () {
        $data = ['key' => 'value', 'nested' => ['a' => 1]];

        $result = $this->normalizer->normalize($data);

        expect($result)->toBe($data);
    });

    it('returns null unchanged', function () {
        $result = $this->normalizer->normalize(null);

        expect($result)->toBeNull();
    });

    it('returns integer data unchanged', function () {
        $result = $this->normalizer->normalize(42);

        expect($result)->toBe(42);
    });

    it('returns float data unchanged', function () {
        $result = $this->normalizer->normalize(3.14);

        expect($result)->toBe(3.14);
    });

    it('returns boolean data unchanged', function () {
        expect($this->normalizer->normalize(true))->toBeTrue()
            ->and($this->normalizer->normalize(false))->toBeFalse();
    });

    it('returns empty string unchanged', function () {
        $result = $this->normalizer->normalize('');

        expect($result)->toBe('');
    });

    it('ignores attribute and options parameters', function () {
        $result = $this->normalizer->normalize('test_value', null, ['format' => 'datagrid']);

        expect($result)->toBe('test_value');
    });
});
