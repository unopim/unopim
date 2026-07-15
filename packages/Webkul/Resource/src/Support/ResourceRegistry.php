<?php

namespace Webkul\Resource\Support;

use Illuminate\Contracts\Container\Container;
use Webkul\Resource\Contracts\ResourceInterface;

class ResourceRegistry
{
    protected array $resources = [];

    public function __construct(protected Container $container) {}

    /**
     * Register a resource class under a given name.
     *
     * @param  class-string<ResourceInterface>  $resourceClass
     */
    public function register(string $name, string $resourceClass): void
    {
        $this->resources[$name] = $resourceClass;
    }

    /**
     * Determine whether a resource is registered under the given name.
     */
    public function has(string $name): bool
    {
        return isset($this->resources[$name]);
    }

    /**
     * Resolve the registered resource instance by name via the container.
     */
    public function get(string $name): ResourceInterface
    {
        return $this->container->make($this->resources[$name]);
    }

    /**
     * Get all registered resource class names keyed by name.
     *
     * @return array<string, class-string<ResourceInterface>>
     */
    public function all(): array
    {
        return $this->resources;
    }
}
