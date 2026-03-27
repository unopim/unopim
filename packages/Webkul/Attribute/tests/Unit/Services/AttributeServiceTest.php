<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;

beforeEach(function () {
    $this->attributeService = app(AttributeService::class);
});

describe('AttributeService - findAttributeByCode', function () {
    it('returns attribute when code exists', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'test_find_code',
            'type' => 'text',
        ]);

        $found = $this->attributeService->findAttributeByCode('test_find_code');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($attribute->id)
            ->and($found->code)->toBe('test_find_code');
    });

    it('returns null when code does not exist', function () {
        $found = $this->attributeService->findAttributeByCode('nonexistent_code_xyz');

        expect($found)->toBeNull();
    });

    it('caches the result on subsequent calls', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'test_cache_code',
            'type' => 'text',
        ]);

        $first = $this->attributeService->findAttributeByCode('test_cache_code');
        $second = $this->attributeService->findAttributeByCode('test_cache_code');

        expect($first)->not->toBeNull()
            ->and($second)->not->toBeNull()
            ->and($first->id)->toBe($second->id);
    });

    it('returns same instance from cache without additional query', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'test_same_instance',
            'type' => 'text',
        ]);

        $first = $this->attributeService->findAttributeByCode('test_same_instance');

        // Delete from DB to prove second call uses cache, not DB
        Attribute::where('code', 'test_same_instance')->delete();

        $second = $this->attributeService->findAttributeByCode('test_same_instance');

        expect($second)->not->toBeNull()
            ->and($second->id)->toBe($first->id);
    });
});

describe('AttributeService - findByCodes', function () {
    it('returns multiple attributes by codes', function () {
        $attr1 = Attribute::factory()->create(['code' => 'batch_a', 'type' => 'text']);
        $attr2 = Attribute::factory()->create(['code' => 'batch_b', 'type' => 'text']);

        $result = $this->attributeService->findByCodes(['batch_a', 'batch_b']);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and(array_keys($result))->toContain('batch_a', 'batch_b');
    });

    it('handles mix of existing and non-existing codes', function () {
        $attr = Attribute::factory()->create(['code' => 'exists_code', 'type' => 'text']);

        $result = $this->attributeService->findByCodes(['exists_code', 'does_not_exist']);

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('exists_code')
            ->and($result)->not->toHaveKey('does_not_exist');
    });

    it('returns empty array when all codes are non-existing', function () {
        $result = $this->attributeService->findByCodes(['fake_code_1', 'fake_code_2']);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(0);
    });

    it('uses cache for previously fetched codes', function () {
        $attr = Attribute::factory()->create(['code' => 'cached_batch', 'type' => 'text']);

        // First call fetches from DB
        $this->attributeService->findByCodes(['cached_batch']);

        // Delete from DB to prove cache is used
        Attribute::where('code', 'cached_batch')->delete();

        // Second call should use cache
        $result = $this->attributeService->findByCodes(['cached_batch']);

        expect($result)->toHaveCount(1)
            ->and($result)->toHaveKey('cached_batch');
    });

    it('does not re-query non-existing codes on subsequent calls', function () {
        // First call marks 'ghost_code' as non-existing
        $this->attributeService->findByCodes(['ghost_code']);

        // Create the attribute after first call
        Attribute::factory()->create(['code' => 'ghost_code', 'type' => 'text']);

        // Second call should still not find it because non-existing codes are tracked
        $result = $this->attributeService->findByCodes(['ghost_code']);

        expect($result)->toHaveCount(0);
    });
});
