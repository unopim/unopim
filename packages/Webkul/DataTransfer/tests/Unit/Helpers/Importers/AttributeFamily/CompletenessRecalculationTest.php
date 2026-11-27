<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Storage;

/**
 * Import one row for the family's sku attribute with the given completeness channels.
 */
function importFamilyCompleteness(AttributeFamily $family, string $completeness): void
{
    $storage = app(Storage::class);
    $storage->load([$family->code]);

    $importer = app()->make(Importer::class, ['attributeFamilyStorage' => $storage]);

    $families = [];

    $importer->prepareAttributeFamilies([
        'code'            => $family->code,
        'locale'          => 'en_US',
        'name'            => $family->name,
        'attribute_group' => $family->familyGroups->first()->code,
        'attributes'      => 'sku',
        'completeness'    => $completeness,
    ], $families);

    $importer->saveAttributeFamilies($families);
}

function dispatchedCompletenessFamilyIds(): array
{
    return Bus::dispatched(BulkProductCompletenessJob::class)
        ->map(fn (BulkProductCompletenessJob $job) => (fn () => $this->familyId)->call($job))
        ->all();
}

beforeEach(function (): void {
    Bus::fake([BulkProductCompletenessJob::class]);

    $this->family = AttributeFamily::factory()->withRequiredAttributes()->create();
    $this->skuId = DB::table('attributes')->where('code', 'sku')->value('id');
    $this->channelId = core()->getDefaultChannel()->id;
});

it('recalculates the family completeness when the import changes its completeness settings', function (): void {
    importFamilyCompleteness($this->family, core()->getDefaultChannel()->code);

    expect(DB::table('completeness_settings')->where('family_id', $this->family->id)->pluck('attribute_id')->all())
        ->toBe([$this->skuId])
        ->and(dispatchedCompletenessFamilyIds())->toBe([$this->family->id]);
});

it('does not recalculate the family completeness when the import leaves its settings unchanged', function (): void {
    DB::table('completeness_settings')->insert([
        'family_id'    => $this->family->id,
        'attribute_id' => $this->skuId,
        'channel_id'   => $this->channelId,
    ]);

    importFamilyCompleteness($this->family, core()->getDefaultChannel()->code);

    expect(dispatchedCompletenessFamilyIds())->toBe([]);
});
