<?php

namespace Webkul\Resource\Contracts;

use Webkul\Resource\Support\FieldSchema;

interface ResourceInterface
{
    /**
     * Get the FQCN of the repository used to persist this resource.
     *
     * @return class-string
     */
    public function repository(): string;

    /**
     * Get the FQCN of the DataGrid used to list this resource.
     *
     * @return class-string
     */
    public function dataGrid(): string;

    /**
     * Get the FQCN of the FormRequest used to validate store/update input.
     *
     * @return class-string
     */
    public function request(): string;

    /**
     * Get the dot-prefixed route name prefix (e.g. "admin.resource-kit-items").
     */
    public function routePrefix(): string;

    /**
     * Get the ACL permission prefix used for bouncer checks.
     */
    public function aclPrefix(): string;

    /**
     * Get the field schema describing this resource's form/grid fields.
     */
    public function schema(): FieldSchema;

    /**
     * Build the array consumed by the resource's Blade views.
     */
    public function toViewModel(): array;
}
