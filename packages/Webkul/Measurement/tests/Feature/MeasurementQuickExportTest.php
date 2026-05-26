<?php

use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;

it('formats measurement values for product quick export', function () {
    $attribute = Mockery::mock(AttributeContract::class);
    $attribute->type = 'measurement';

    $attributeService = Mockery::mock(AttributeService::class);
    $attributeService
        ->shouldReceive('findAttributeByCode')
        ->with('measurement')
        ->andReturn($attribute);

    $measurementHelper = Mockery::mock(MeasurementHelper::class);
    $measurementHelper
        ->shouldReceive('getUnitLabel')
        ->with('kg', $attribute, 'en_US')
        ->andReturn('Kilogram');

    $normalizer = new ProductAttributeValuesNormalizer($attributeService, $measurementHelper);

    $values = $normalizer->normalizeAttributes([
        'measurement' => [
            'amount' => '12.50',
            'unit'   => 'kg',
        ],
    ], [
        'forExport' => true,
        'locale'    => 'en_US',
    ]);

    expect($values)->toEqual([
        'measurement'       => '12.50',
        'measurement(unit)' => 'Kilogram',
    ]);
})->group('measurement', 'admin');

it('resolves measurement unit labels and codes', function () {
    $attribute = Mockery::mock(AttributeContract::class);
    $attribute->id = 1;

    $attributeMeasurement = (object) [
        'family' => (object) [
            'units' => [
                [
                    'code'   => 'kg',
                    'symbol' => 'kg',
                    'labels' => [
                        'en_US' => 'Kilogram',
                        'hi_IN' => 'किलोग्राम',
                    ],
                ],
            ],
        ],
    ];

    $repository = Mockery::mock(AttributeMeasurementRepository::class);
    $repository
        ->shouldReceive('getByAttributeId')
        ->with(1)
        ->andReturn($attributeMeasurement);

    $helper = new MeasurementHelper($repository);

    expect($helper->getUnitLabel('kg', $attribute, 'en_US'))->toBe('Kilogram');
    expect($helper->resolveUnitCode('Kilogram', $attribute, 'en_US'))->toBe('kg');
    expect($helper->resolveUnitCode('किलोग्राम', $attribute, 'hi_IN'))->toBe('kg');
})->group('measurement', 'admin');
