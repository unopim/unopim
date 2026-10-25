<?php

namespace Webkul\Publication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Publication\Models\PublicationCarrierIssuance;

/**
 * @extends Factory<PublicationCarrierIssuance>
 */
class PublicationCarrierIssuanceFactory extends Factory
{
    protected $model = PublicationCarrierIssuance::class;

    public function definition(): array
    {
        return [
            'publication_id' => PublicationFactory::new(),
            'release_id'     => PublicationReleaseFactory::new(),
            'target'         => 'https://example.test/p/uuid/r/1',
            'format'         => 'svg',
            'issued_at'      => now(),
        ];
    }
}
