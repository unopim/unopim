<?php

use Webkul\DataTransfer\Helpers\Sources\Export\Filters\AttributeConditionOperators as Ops;

describe('Attribute condition operators by type', function () {
    it('offers option operators for a select attribute', function () {
        expect(Ops::forType('select'))->toBe([Ops::IN, Ops::NOT_IN, Ops::EMPTY, Ops::NOT_EMPTY]);
    });

    it('offers numeric operators for a price attribute', function () {
        expect(Ops::forType('price'))
            ->toContain(Ops::LESS_THAN, Ops::GREATER_THAN, Ops::BETWEEN, Ops::EMPTY);
    });

    it('offers date operators for a date attribute', function () {
        expect(Ops::forType('date'))->toBe([Ops::BEFORE, Ops::AFTER, Ops::BETWEEN, Ops::EMPTY, Ops::NOT_EMPTY]);
    });

    it('offers a single equals operator for a boolean attribute', function () {
        expect(Ops::forType('boolean'))->toBe([Ops::EQUALS]);
    });

    it('offers text operators for an unknown / text attribute', function () {
        expect(Ops::forType('text'))->toBe([Ops::CONTAINS, Ops::EQUALS, Ops::EMPTY, Ops::NOT_EMPTY]);
        expect(Ops::forType('whatever'))->toBe([Ops::CONTAINS, Ops::EQUALS, Ops::EMPTY, Ops::NOT_EMPTY]);
    });
});

describe('Attribute condition value controls', function () {
    it('needs no value for empty operators', function () {
        expect(Ops::valueControl('price', Ops::EMPTY))->toBe('none');
        expect(Ops::valueControl('select', Ops::NOT_EMPTY))->toBe('none');
    });

    it('uses an options control for option attributes', function () {
        expect(Ops::valueControl('select', Ops::IN))->toBe('options');
        expect(Ops::valueControl('multiselect', Ops::NOT_IN))->toBe('options');
    });

    it('uses a boolean control for boolean attributes', function () {
        expect(Ops::valueControl('boolean', Ops::EQUALS))->toBe('boolean');
    });

    it('uses a number range for a numeric between', function () {
        expect(Ops::valueControl('price', Ops::BETWEEN))->toBe('number_range');
    });

    it('uses a date range for a date between', function () {
        expect(Ops::valueControl('date', Ops::BETWEEN))->toBe('date_range');
    });

    it('uses a single number control for a numeric comparison', function () {
        expect(Ops::valueControl('price', Ops::LESS_THAN))->toBe('number');
    });

    it('uses a single date control for a date comparison', function () {
        expect(Ops::valueControl('date', Ops::BEFORE))->toBe('date');
    });

    it('uses a text control for a text comparison', function () {
        expect(Ops::valueControl('text', Ops::CONTAINS))->toBe('text');
    });
});

describe('Attribute condition frontend map', function () {
    it('maps every known type to operator option objects', function () {
        $map = Ops::frontendMap();

        expect($map)->toHaveKeys(['text', 'price', 'select', 'date', 'boolean']);

        $priceOption = collect($map['price'])->firstWhere('value', Ops::BETWEEN);

        expect($priceOption)
            ->toHaveKeys(['value', 'label', 'control'])
            ->and($priceOption['control'])->toBe('number_range');
    });
});
