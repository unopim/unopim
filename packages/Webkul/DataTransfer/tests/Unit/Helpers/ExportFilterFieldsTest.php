<?php

use Webkul\DataTransfer\Helpers\ExportFilterFields;

it('shows the generic filters card only for exporters that opt in', function () {
    $exporters = [
        'records'   => ['filters' => ['generic_card' => true, 'fields' => [['name' => 'entity_type']]]],
        'connector' => ['filters' => ['fields' => [['name' => 'credentials']]]],
        'products'  => ['filters' => ['fields' => [['name' => 'channels']]]],
    ];

    expect(ExportFilterFields::genericCardTypes($exporters))->toBe(['records'])
        ->and(ExportFilterFields::usesGenericCard($exporters['connector']))->toBeFalse()
        ->and(ExportFilterFields::usesGenericCard(null))->toBeFalse();
});

it('passes the fields laid out in their own cards to the generic card as one exclusion list', function () {
    expect(ExportFilterFields::knownList())->toBe(implode(',', ExportFilterFields::KNOWN))
        ->and(ExportFilterFields::KNOWN)->toContain('channels', 'file_format', 'file_path');
});

it('keeps an exporter out of the generic card when it sets generic_card to false', function () {
    expect(ExportFilterFields::genericCardTypes(['records' => ['filters' => ['generic_card' => false]]]))->toBe([]);
});
