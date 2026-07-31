<?php

namespace Webkul\Resource\Tests\Fixtures;

use Webkul\Resource\Support\AbstractResource;
use Webkul\Resource\Support\Field;
use Webkul\Resource\Support\FieldSchema;

class TestResource extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    public function repository(): string
    {
        return TestRepository::class;
    }

    /**
     * {@inheritDoc}
     */
    public function dataGrid(): string
    {
        return TestDataGrid::class;
    }

    /**
     * {@inheritDoc}
     */
    public function request(): string
    {
        return TestForm::class;
    }

    /**
     * {@inheritDoc}
     */
    public function routePrefix(): string
    {
        return 'admin.resource-kit-items';
    }

    /**
     * {@inheritDoc}
     */
    public function aclPrefix(): string
    {
        return 'resource-kit-items';
    }

    /**
     * {@inheritDoc}
     */
    public function schema(): FieldSchema
    {
        return FieldSchema::make([
            Field::text('name')->required()->rules('required'),
            // No ->rules(): covers persisting a schema field outside validated() (see schemaFieldNames()).
            Field::text('label'),
        ]);
    }
}
