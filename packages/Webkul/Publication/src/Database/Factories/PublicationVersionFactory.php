<?php

namespace Webkul\Publication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Locale;
use Webkul\Publication\Models\PublicationVersion;

/**
 * @extends Factory<PublicationVersion>
 */
class PublicationVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PublicationVersion::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'publication_id' => PublicationFactory::new(),
            'locale_id'      => Locale::first()?->id ?? Locale::factory(),
            'version'        => 1,
            'payload'        => ['sections' => []],
            'checksum'       => hash('sha256', 'seed'),
            'is_current'     => false,
            'published_at'   => now(),
        ];
    }
}
