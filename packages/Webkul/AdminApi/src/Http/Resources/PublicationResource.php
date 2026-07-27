<?php

namespace Webkul\AdminApi\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'status'       => $this->status->value,
            'type'         => $this->type,
            'product_sku'  => $this->whenLoaded('product', fn () => $this->product?->sku),
            'channel'      => $this->whenLoaded('channel', fn () => $this->channel?->code),
            'public_url'   => route('publication.public.dpp.show', ['uuid' => $this->uuid]),
            'published_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
