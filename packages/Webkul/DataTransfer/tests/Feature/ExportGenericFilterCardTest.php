<?php

use Webkul\DataTransfer\Helpers\ExportFilterFields;
use Webkul\DataTransfer\Models\JobInstances;

function genericCardExport(bool $genericCard): JobInstances
{
    config(['exporters.generic_records' => [
        'title'   => 'Generic records',
        'filters' => array_filter([
            'generic_card' => $genericCard,
            'fields'       => [
                ['name' => 'record_type', 'title' => 'Record type', 'type' => 'text', 'required' => false],
                ['name' => 'file_format', 'title' => 'File format', 'type' => 'text', 'required' => false],
            ],
        ]),
    ]]);

    return JobInstances::factory()->exportJob()->create(['entity_type' => 'generic_records', 'filters' => []]);
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('lays out the other filter fields in the generic card for an exporter that opts in', function () {
    $export = genericCardExport(true);

    $this->get(route('admin.settings.data_transfer.exports.edit', $export->id))
        ->assertOk()
        ->assertSee('except="'.ExportFilterFields::knownList().'"', false);
});

it('leaves the generic card out for an exporter that does not opt in', function () {
    $export = genericCardExport(false);

    $this->get(route('admin.settings.data_transfer.exports.edit', $export->id))
        ->assertOk()
        ->assertDontSee('except="'.ExportFilterFields::knownList().'"', false);
});
