<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Storage;

/**
 * Import one row for the family's sku attribute with the given completeness value.
 */
function importFamilyRow(AttributeFamily $family, ?string $completeness): void
{
    $storage = app(Storage::class);
    $storage->load([$family->code]);

    $importer = app()->make(Importer::class, ['attributeFamilyStorage' => $storage]);

    $row = [
        'code'            => $family->code,
        'locale'          => 'en_US',
        'name'            => $family->name,
        'attribute_group' => $family->familyGroups->first()->code,
        'attributes'      => 'sku',
    ];

    if ($completeness !== null) {
        $row['completeness'] = $completeness;
    }

    $families = [];

    $importer->prepareAttributeFamilies($row, $families);
    $importer->saveAttributeFamilies($families);
}

function familyCompletenessChannels(AttributeFamily $family): array
{
    return DB::table('completeness_settings')->where('family_id', $family->id)->pluck('channel_id')->all();
}

beforeEach(function (): void {
    $this->family = AttributeFamily::factory()->withRequiredAttributes()->create();
    $this->channel = core()->getDefaultChannel();

    DB::table('completeness_settings')->insert([
        'family_id'    => $this->family->id,
        'attribute_id' => DB::table('attributes')->where('code', 'sku')->value('id'),
        'channel_id'   => $this->channel->id,
    ]);
});

it('keeps the family completeness settings when the file has no completeness values', function (?string $completeness): void {
    importFamilyRow($this->family, $completeness);

    expect(familyCompletenessChannels($this->family))->toBe([$this->channel->id]);
})->with([
    'empty completeness column' => [''],
    'no completeness column'    => [null],
]);

it('still replaces the family completeness settings when the file has completeness values', function (): void {
    $otherChannel = Channel::factory()->create();

    importFamilyRow($this->family, $otherChannel->code);

    expect(familyCompletenessChannels($this->family))->toBe([$otherChannel->id]);
});
