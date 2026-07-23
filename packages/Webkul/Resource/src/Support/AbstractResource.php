<?php

namespace Webkul\Resource\Support;

use Webkul\Resource\Contracts\ResourceInterface;

abstract class AbstractResource implements ResourceInterface
{
    /**
     * Build the array consumed by the resource's Blade views (routes, schema, ACL/route prefixes).
     */
    public function toViewModel(): array
    {
        return [
            'routePrefix' => $this->routePrefix(),
            'aclPrefix'   => $this->aclPrefix(),
            'schema'      => $this->schema()->toArray(),
            'urls'        => [
                'index'  => route($this->routePrefix().'.index'),
                'create' => route($this->routePrefix().'.create'),
                'store'  => route($this->routePrefix().'.store'),
                'update' => route($this->routePrefix().'.update', ':id'),
            ],
        ];
    }
}
