<?php

use Webkul\Product\Enums\VariantLevel;
use Webkul\Product\Services\VariantStructurePlanner;
use Webkul\Product\Services\VariantStructureWriter;

describe('VariantLevel', function () {
    it('keeps the legacy string values stored in variant_structure_attributes.level', function () {
        expect(VariantLevel::VALUES)->toBe(['common', 'sub_parent', 'variant']);
    });

    it('lists every case in VALUES and ORDER', function () {
        expect(VariantLevel::VALUES)->toBe(array_column(VariantLevel::cases(), 'value'))
            ->and(array_keys(VariantLevel::ORDER))->toBe(VariantLevel::VALUES);
    });

    it('orders the levels from the root down to the leaf', function (VariantLevel $level, int $order) {
        expect($level->order())->toBe($order);
    })->with([
        [VariantLevel::Common, 0],
        [VariantLevel::SubParent, 1],
        [VariantLevel::Variant, 2],
    ]);

    it('keeps the deprecated placement level constants equal to the enum values', function () {
        expect(VariantStructureWriter::PLACEMENT_LEVELS)->toBe(VariantLevel::VALUES);

        foreach (VariantLevel::cases() as $level) {
            expect(VariantStructurePlanner::LEVEL_ORDER[$level->value])->toBe($level->order());
        }

        expect(VariantStructurePlanner::LEVEL_ORDER)->toHaveCount(count(VariantLevel::cases()));
    });
});
