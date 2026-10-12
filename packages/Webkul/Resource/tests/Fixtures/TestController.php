<?php

namespace Webkul\Resource\Tests\Fixtures;

use Webkul\Resource\Contracts\ResourceInterface;
use Webkul\Resource\Http\Controllers\AbstractResourceController;
use Webkul\Resource\Support\ResourceRegistry;

class TestController extends AbstractResourceController
{
    /**
     * {@inheritDoc}
     */
    protected function resource(): ResourceInterface
    {
        return app(ResourceRegistry::class)->get('resource-kit-items');
    }
}
