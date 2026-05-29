<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     */
    #[\Override]
    public function toArray($request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->label ?? $this->admin_name,
        ];
    }
}
