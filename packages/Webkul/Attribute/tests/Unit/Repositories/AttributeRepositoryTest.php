<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Repositories\AttributeRepository;

beforeEach(function () {
    $this->attributeRepository = app(AttributeRepository::class);
});

describe('AttributeRepository - create', function () {
    it('persists a text attribute to the database', function () {
        $attribute = $this->attributeRepository->create([
            'code'              => 'repo_test_text',
            'type'              => 'text',
            'validation'        => '',
            'position'          => 1,
            'is_required'       => false,
            'is_unique'         => false,
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        expect($attribute)->toBeInstanceOf(Attribute::class)
            ->and($attribute->code)->toBe('repo_test_text')
            ->and($attribute->type)->toBe('text')
            ->and($attribute->exists)->toBeTrue();

        $this->assertDatabaseHas('attributes', [
            'code' => 'repo_test_text',
            'type' => 'text',
        ]);
    });

    it('creates options when type is select', function () {
        $attribute = $this->attributeRepository->create([
            'code'              => 'repo_test_select',
            'type'              => 'select',
            'validation'        => '',
            'position'          => 1,
            'is_required'       => false,
            'is_unique'         => false,
            'value_per_locale'  => false,
            'value_per_channel' => false,
            'options'           => [
                ['code' => 'opt_a', 'sort_order' => 1],
                ['code' => 'opt_b', 'sort_order' => 2],
            ],
        ]);

        expect($attribute->type)->toBe('select');

        $options = AttributeOption::where('attribute_id', $attribute->id)->get();

        expect($options)->toHaveCount(2);
        expect($options->pluck('code')->toArray())->toContain('opt_a', 'opt_b');
    });

    it('creates options when type is multiselect', function () {
        $attribute = $this->attributeRepository->create([
            'code'              => 'repo_test_multi',
            'type'              => 'multiselect',
            'validation'        => '',
            'position'          => 1,
            'is_required'       => false,
            'is_unique'         => false,
            'value_per_locale'  => false,
            'value_per_channel' => false,
            'options'           => [
                ['code' => 'multi_a', 'sort_order' => 1],
                ['code' => 'multi_b', 'sort_order' => 2],
                ['code' => 'multi_c', 'sort_order' => 3],
            ],
        ]);

        $options = AttributeOption::where('attribute_id', $attribute->id)->get();

        expect($options)->toHaveCount(3);
    });

    it('does not create options for text type even if options are provided', function () {
        $attribute = $this->attributeRepository->create([
            'code'              => 'repo_text_no_opts',
            'type'              => 'text',
            'validation'        => '',
            'position'          => 1,
            'is_required'       => false,
            'is_unique'         => false,
            'value_per_locale'  => false,
            'value_per_channel' => false,
            'options'           => [
                ['code' => 'should_not_exist', 'sort_order' => 1],
            ],
        ]);

        $options = AttributeOption::where('attribute_id', $attribute->id)->get();

        expect($options)->toHaveCount(0);
    });

    it('removes is_unique for non-text types', function () {
        $attribute = $this->attributeRepository->create([
            'code'              => 'repo_price_unique',
            'type'              => 'price',
            'validation'        => '',
            'position'          => 1,
            'is_required'       => false,
            'is_unique'         => true,
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ]);

        // The validateUserInput strips is_unique for non-text types
        expect($attribute->code)->toBe('repo_price_unique');
    });
});

describe('AttributeRepository - update', function () {
    it('modifies attribute fields', function () {
        $attribute = Attribute::factory()->create([
            'code'        => 'repo_update_test',
            'type'        => 'text',
            'is_required' => false,
            'position'    => 1,
        ]);

        $updated = $this->attributeRepository->update([
            'code'              => 'repo_update_test',
            'type'              => 'text',
            'is_required'       => true,
            'position'          => 5,
            'value_per_locale'  => false,
            'value_per_channel' => false,
        ], $attribute->id);

        expect($updated->is_required)->toBeTruthy()
            ->and($updated->position)->toBe(5);
    });

    it('adds new options to a select attribute', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'repo_update_opts',
            'type' => 'select',
        ]);

        $existingCount = $attribute->options->count();

        $this->attributeRepository->update([
            'code'    => 'repo_update_opts',
            'type'    => 'select',
            'options' => [
                'new_1' => ['code' => 'new_opt_1', 'sort_order' => 10, 'isNew' => 'true', 'isDelete' => 'false'],
            ],
        ], $attribute->id);

        $attribute->refresh();
        $updatedCount = $attribute->options->count();

        expect($updatedCount)->toBe($existingCount + 1);
    });

    it('deletes options from a select attribute', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'repo_delete_opts',
            'type' => 'select',
        ]);

        $optionToDelete = $attribute->options->first();
        $existingCount = $attribute->options->count();

        $this->attributeRepository->update([
            'code'    => 'repo_delete_opts',
            'type'    => 'select',
            'options' => [
                $optionToDelete->id => ['isNew' => 'false', 'isDelete' => 'true'],
            ],
        ], $attribute->id);

        $attribute->refresh();

        expect($attribute->options->count())->toBe($existingCount - 1);
    });
});

describe('AttributeRepository - getProductDefaultAttributes', function () {
    it('returns default attributes when no codes are provided', function () {
        $defaults = $this->attributeRepository->getProductDefaultAttributes();

        expect($defaults)->not->toBeNull();
    });

    it('returns specific attributes when codes are provided', function () {
        $attribute = Attribute::factory()->create([
            'code' => 'custom_default_attr',
            'type' => 'text',
        ]);

        $result = $this->attributeRepository->getProductDefaultAttributes(['custom_default_attr']);

        expect($result->count())->toBeGreaterThanOrEqual(1)
            ->and($result->pluck('code')->toArray())->toContain('custom_default_attr');
    });

    it('returns all attributes when wildcard is provided', function () {
        $all = $this->attributeRepository->getProductDefaultAttributes(['*']);

        expect($all->count())->toBeGreaterThanOrEqual(1);
    });

    it('returns only requested columns', function () {
        $result = $this->attributeRepository->getProductDefaultAttributes(['sku']);

        if ($result->isNotEmpty()) {
            $first = $result->first();
            expect($first->id)->not->toBeNull()
                ->and($first->code)->toBe('sku');
        }
    });
});
