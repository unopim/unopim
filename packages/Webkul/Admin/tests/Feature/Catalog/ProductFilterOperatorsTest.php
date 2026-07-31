<?php

use Webkul\Admin\Filters\ProductFilterOperators;
use Webkul\Admin\Traits\AttributeColumnTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\DataGrid\Column;
use Webkul\ElasticSearch\Enums\FilterOperators;

function operatorValues(?string $type): array
{
    return array_column(ProductFilterOperators::optionsForType($type), 'value');
}

describe('the product datagrid offers operators that its filters can actually run', function () {

    it('offers numeric comparisons for a price', function () {
        expect(operatorValues(Attribute::PRICE_FIELD_TYPE))->toBe([
            FilterOperators::EQUAL->value,
            FilterOperators::LESS_THAN->value,
            FilterOperators::LESS_THAN_OR_EQUAL->value,
            FilterOperators::GREATER_THAN->value,
            FilterOperators::GREATER_THAN_OR_EQUAL->value,
            FilterOperators::RANGE->value,
            FilterOperators::IS_EMPTY->value,
            FilterOperators::IS_NOT_EMPTY->value,
        ]);
    });

    it('offers list membership for an option attribute', function () {
        expect(operatorValues(Attribute::SELECT_FIELD_TYPE))->toBe([
            FilterOperators::IN->value,
            FilterOperators::NOT_IN->value,
            FilterOperators::IS_EMPTY->value,
            FilterOperators::IS_NOT_EMPTY->value,
        ]);
    });

    it('offers before and after for a date', function () {
        expect(operatorValues(Attribute::DATE_FIELD_TYPE))
            ->toContain(FilterOperators::LESS_THAN->value)
            ->toContain(FilterOperators::GREATER_THAN->value)
            ->toContain(FilterOperators::RANGE->value);
    });

    it('offers only equals for a boolean', function () {
        expect(operatorValues(Attribute::BOOLEAN_FIELD_TYPE))->toBe([FilterOperators::EQUAL->value]);
    });

    it('falls back to text operators for an unknown type', function () {
        expect(operatorValues('something-else'))->toBe([
            FilterOperators::CONTAINS->value,
            FilterOperators::EQUAL->value,
            FilterOperators::IS_EMPTY->value,
            FilterOperators::IS_NOT_EMPTY->value,
        ]);
    });

    it('labels every operator', function () {
        foreach (ProductFilterOperators::optionsForType(Attribute::PRICE_FIELD_TYPE) as $operator) {
            expect($operator['label'])->not->toBeEmpty()
                ->and($operator['label'])->not->toContain('admin::app');
        }
    });

    it('maps every known attribute type', function () {
        $map = ProductFilterOperators::frontendMap();

        expect($map)->toHaveKeys([
            Attribute::TEXT_TYPE,
            Attribute::PRICE_FIELD_TYPE,
            Attribute::SELECT_FIELD_TYPE,
            Attribute::BOOLEAN_FIELD_TYPE,
            Attribute::DATE_FIELD_TYPE,
        ]);
    });
});

describe('the value input follows the chosen operator', function () {

    it('asks for no value on the empty checks', function () {
        expect(ProductFilterOperators::valueControl(Attribute::PRICE_FIELD_TYPE, FilterOperators::IS_EMPTY))->toBe('none')
            ->and(ProductFilterOperators::valueControl(Attribute::TEXT_TYPE, FilterOperators::IS_NOT_EMPTY))->toBe('none');
    });

    it('asks for two numbers on a numeric range', function () {
        expect(ProductFilterOperators::valueControl(Attribute::PRICE_FIELD_TYPE, FilterOperators::RANGE))->toBe('number_range');
    });

    it('asks for two dates on a date range', function () {
        expect(ProductFilterOperators::valueControl(Attribute::DATE_FIELD_TYPE, FilterOperators::RANGE))->toBe('date_range');
    });

    it('asks for an option list on an option attribute', function () {
        expect(ProductFilterOperators::valueControl(Attribute::SELECT_FIELD_TYPE, FilterOperators::IN))->toBe('options');
    });

    it('asks for a boolean on a boolean attribute', function () {
        expect(ProductFilterOperators::valueControl(Attribute::BOOLEAN_FIELD_TYPE, FilterOperators::EQUAL))->toBe('boolean');
    });

    it('asks for a number on a price and text elsewhere', function () {
        expect(ProductFilterOperators::valueControl(Attribute::PRICE_FIELD_TYPE, FilterOperators::EQUAL))->toBe('number')
            ->and(ProductFilterOperators::valueControl(Attribute::TEXT_TYPE, FilterOperators::CONTAINS))->toBe('text');
    });
});

describe('the datagrid column carries the attribute type and its operators', function () {

    it('keeps attribute_type and operators on the column', function () {
        $column = new Column(
            index: 'price',
            label: 'Price',
            type: 'price',
            options: [],
            searchable: false,
            filterable: true,
            sortable: true,
            attribute_type: 'price',
            operators: ProductFilterOperators::optionsForType('price'),
        );

        expect($column->attribute_type)->toBe('price')
            ->and($column->operators)->not->toBeEmpty();

        $encoded = json_decode(json_encode($column), true);

        expect($encoded)->toHaveKey('attribute_type')
            ->and($encoded['attribute_type'])->toBe('price')
            ->and($encoded['operators'][0])->toHaveKeys(['value', 'label', 'control']);
    });

    it('leaves them null for a plain property column', function () {
        $column = new Column(
            index: 'status',
            label: 'Status',
            type: 'boolean',
            options: [],
            searchable: false,
            filterable: true,
            sortable: true,
        );

        expect($column->attribute_type)->toBeNull()
            ->and($column->operators)->toBeNull();
    });
});

describe('an attribute column is built with the inputs the filter drawer needs', function () {

    $builder = fn () => new class
    {
        use AttributeColumnTrait;

        public function build($attribute): array
        {
            return $this->buildColumnDefinition($attribute);
        }
    };

    it('tags a price column with its type and numeric operators', function () use ($builder) {
        $column = $builder()->build(Attribute::where('code', 'price')->firstOrFail());

        expect($column['attribute_type'])->toBe(Attribute::PRICE_FIELD_TYPE)
            ->and(array_column($column['operators'], 'value'))
            ->toContain(FilterOperators::GREATER_THAN->value)
            ->toContain(FilterOperators::RANGE->value);
    });

    it('tags a text column with contains rather than numeric operators', function () use ($builder) {
        $column = $builder()->build(Attribute::where('code', 'name')->firstOrFail());

        $operators = array_column($column['operators'], 'value');

        expect($column['attribute_type'])->toBe(Attribute::TEXT_TYPE)
            ->and($operators)->toContain(FilterOperators::CONTAINS->value)
            ->and($operators)->not->toContain(FilterOperators::GREATER_THAN->value);
    });

    it('tags an option column with list operators', function () use ($builder) {
        $column = $builder()->build(Attribute::where('code', 'color')->firstOrFail());

        expect($column['attribute_type'])->toBe(Attribute::SELECT_FIELD_TYPE)
            ->and(array_column($column['operators'], 'value'))
            ->toContain(FilterOperators::IN->value)
            ->toContain(FilterOperators::NOT_IN->value);
    });
});

describe('operators are config-driven, so packages can extend them without touching core', function () {

    it('offers operators for a brand-new attribute type registered only via config', function () {
        config()->set('product_filter_operators.groups.custom_group', [
            'types'     => ['xyz'],
            'control'   => 'text',
            'operators' => [
                ['operator' => FilterOperators::IN->value, 'label' => 'in'],
                ['operator' => FilterOperators::IS_EMPTY->value, 'label' => 'empty'],
            ],
        ]);

        $options = ProductFilterOperators::optionsForType('xyz');

        expect(array_column($options, 'value'))->toBe([
            FilterOperators::IN->value,
            FilterOperators::IS_EMPTY->value,
        ])
            ->and($options[0]['control'])->toBe('text')
            ->and($options[1]['control'])->toBe('none')
            ->and(ProductFilterOperators::frontendMap())->toHaveKey('xyz');
    });

    it('lets a package change the operators offered for an existing type', function () {
        config()->set('product_filter_operators.groups.text.operators', [
            ['operator' => FilterOperators::EQUAL->value, 'label' => 'equals'],
        ]);

        expect(operatorValues(Attribute::TEXT_TYPE))->toBe([
            FilterOperators::EQUAL->value,
        ]);
    });
});
