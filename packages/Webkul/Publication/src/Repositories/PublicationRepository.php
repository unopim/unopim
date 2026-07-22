<?php

namespace Webkul\Publication\Repositories;

use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Publication\Contracts\Publication as PublicationContract;
use Webkul\Publication\Models\Publication;

class PublicationRepository extends Repository
{
    public function model(): string
    {
        return PublicationContract::class;
    }

    public function findOrCreateFor(int $productId, int $channelId, string $type): Publication
    {
        return $this->model->firstOrCreate(
            ['product_id' => $productId, 'channel_id' => $channelId, 'type' => $type],
            ['uuid' => (string) Str::uuid7()],
        );
    }
}
