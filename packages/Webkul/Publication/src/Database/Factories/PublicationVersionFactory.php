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
    protected $model = PublicationVersion::class;

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
