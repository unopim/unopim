<?php

use Webkul\AdminApi\Http\Controllers\API\ApiController;

/**
 * Run ApiController::setLabels() on a payload.
 *
 * @param  array<string, mixed>  $payload
 * @return array<string, mixed>
 */
function hoistApiLabels(array $payload, string $labelKey = 'name'): array
{
    $controller = new class extends ApiController
    {
        /**
         * @param  array<string, mixed>  $payload
         * @return array<string, mixed>
         */
        public function hoist(array $payload, string $labelKey): array
        {
            return $this->setLabels($payload, $labelKey);
        }
    };

    return $controller->hoist($payload, $labelKey);
}

it('hoists labels keyed by an active locale code', function () {
    expect(hoistApiLabels(['code' => 'color', 'labels' => ['en_US' => 'Colour']]))
        ->toBe(['code' => 'color', 'en_US' => ['name' => 'Colour']]);
});

it('never hoists a labels key that is not an active locale code', function () {
    $hoisted = hoistApiLabels([
        'labels' => [
            'en_US'               => 'Colour',
            'is_unique'           => 1,
            'options'             => ['code' => 'smuggled', 'isNew' => true],
            'fields'              => ['code' => 'smuggled', 'isNew' => true],
            'attribute_id'        => 99,
            'association_type_id' => 99,
            'unknown_LOCALE'      => 'x',
        ],
    ], 'label');

    expect($hoisted)->toBe(['en_US' => ['label' => 'Colour']]);
});
