<?php

namespace Webkul\Publication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Publication\Models\PublicationRelease;

/**
 * @extends Factory<PublicationRelease>
 */
class PublicationReleaseFactory extends Factory
{
    protected $model = PublicationRelease::class;

    public function definition(): array
    {
        return [
            'publication_id' => PublicationFactory::new(),
            'sequence'       => 1,
            'published_at'   => now(),
        ];
    }
}
