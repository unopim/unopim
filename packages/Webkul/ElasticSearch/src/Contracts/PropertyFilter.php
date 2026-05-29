<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Contracts;

interface PropertyFilter extends Filter
{
    /**
     * Add an attribute to filter
     */
    public function applyPropertyFilter(mixed $property, mixed $operator, mixed $value, mixed $locale = null, mixed $channel = null, mixed $options = []): static;

    /**
     * This filter supports the property
     */
    public function supportsProperty(mixed $property): bool;

    /**
     * Returns supported properties
     */
    public function getProperties(): array;
}
