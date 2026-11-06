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
