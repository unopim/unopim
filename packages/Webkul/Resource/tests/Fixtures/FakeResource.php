<?php

namespace Webkul\Resource\Tests\Fixtures;

use Webkul\Resource\Support\AbstractResource;
use Webkul\Resource\Support\Field;
use Webkul\Resource\Support\FieldSchema;

/**
 * Minimal resource double shared by AbstractResourceTest and ResourceRegistryTest.
 * Namespaced (unlike a global-scope class) so either test can run in isolation.
 */
class FakeResource extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    public function repository(): string
    {
        return 'FakeRepo';
    }

    /**
     * {@inheritDoc}
     */
    public function dataGrid(): string
    {
        return 'FakeGrid';
    }

    /**
     * {@inheritDoc}
     */
    public function request(): string
    {
        return 'FakeRequest';
    }

    /**
     * {@inheritDoc}
     */
    public function routePrefix(): string
    {
        return 'admin.fakes';
    }

    /**
     * {@inheritDoc}
     */
    public function aclPrefix(): string
    {
        return 'fakes';
    }

    /**
     * {@inheritDoc}
     */
    public function schema(): FieldSchema
    {
        return FieldSchema::make([Field::text('name')->rules('required')]);
    }
}
