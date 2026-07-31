<?php

namespace Webkul\Publication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\Core\Models\Channel;
use Webkul\Product\Database\Factories\ProductFactory;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\Publication;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    public function definition(): array
    {
        return [
            'uuid'       => (string) Str::uuid(),
            'product_id' => ProductFactory::new(),
            'channel_id' => Channel::first()?->id ?? Channel::factory(),
            'type'       => 'dpp',
            'status'     => PublicationStatus::Published,
        ];
    }
}
