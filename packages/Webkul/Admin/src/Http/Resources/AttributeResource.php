<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
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
            'id'      => $this->id,
            'code'    => $this->code,
            'type'    => $this->type,
            'name'    => $this->admin_name,
            'options' => AttributeOptionResource::collection($this->options),
        ];
    }
}
