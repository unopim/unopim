<?php

use Webkul\Product\Enums\VariantLevelEnum;
use Webkul\Product\Services\VariantStructurePlanner;
use Webkul\Product\Services\VariantStructureWriter;

describe('VariantLevelEnum', function () {
    it('keeps the legacy string values stored in variant_structure_attributes.level', function () {
        expect(VariantLevelEnum::values())->toBe(['common', 'sub_parent', 'variant']);
    });

    it('orders the levels from the root down to the leaf', function (VariantLevelEnum $level, int $order) {
        expect($level->order())->toBe($order);
    })->with([
        [VariantLevelEnum::Common, 0],
        [VariantLevelEnum::SubParent, 1],
        [VariantLevelEnum::Variant, 2],
    ]);

    it('is inherited by its own level and every level below it', function (VariantLevelEnum $placement, VariantLevelEnum $level, bool $inherited) {
        expect($placement->isInheritedBy($level))->toBe($inherited);
    })->with([
        [VariantLevelEnum::Common, VariantLevelEnum::Common, true],
        [VariantLevelEnum::Common, VariantLevelEnum::Variant, true],
        [VariantLevelEnum::SubParent, VariantLevelEnum::Common, false],
        [VariantLevelEnum::SubParent, VariantLevelEnum::Variant, true],
        [VariantLevelEnum::Variant, VariantLevelEnum::SubParent, false],
        [VariantLevelEnum::Variant, VariantLevelEnum::Variant, true],
    ]);

    it('keeps the deprecated placement level constants equal to the enum values', function () {
        expect(VariantStructureWriter::PLACEMENT_LEVELS)->toBe(VariantLevelEnum::values());

        foreach (VariantLevelEnum::cases() as $level) {
            expect(VariantStructurePlanner::LEVEL_ORDER[$level->value])->toBe($level->order());
        }

        expect(VariantStructurePlanner::LEVEL_ORDER)->toHaveCount(count(VariantLevelEnum::cases()));
    });
});
