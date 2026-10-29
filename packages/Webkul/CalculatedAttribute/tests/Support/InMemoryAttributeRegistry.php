<?php

namespace Webkul\CalculatedAttribute\Tests\Support;

use Webkul\CalculatedAttribute\Services\AttributeRegistry;

/**
 * An AttributeRegistry backed by a literal array instead of the database.
 *
 * Formula evaluation has nothing to do with persistence, so the tests that
 * exercise the language itself should not need a schema, a seeded catalogue or
 * a transaction to run.
 */
class InMemoryAttributeRegistry extends AttributeRegistry
{
    /**
     * @param  array<string, array{type?: string, formula?: string|null, per_locale?: bool, per_channel?: bool}>  $definitions
     */
    public function __construct(array $definitions = [])
    {
        $this->attributes = [];

        foreach ($definitions as $code => $definition) {
            $this->define($code, $definition);
        }
    }

    /**
     * Replace one definition, for tests that mutate the graph mid-scenario.
     *
     * @param  array{type?: string, formula?: string|null, per_locale?: bool, per_channel?: bool}  $definition
     */
    public function define(string $code, array $definition): self
    {
        $this->attributes[$code] = [
            'type'        => $definition['type'] ?? 'text',
            'formula'     => $definition['formula'] ?? null,
            'per_locale'  => $definition['per_locale'] ?? false,
            'per_channel' => $definition['per_channel'] ?? false,
        ];

        return $this;
    }

    /**
     * The parent would re-read the database; here the array is the source.
     */
    public function flush(): void
    {
        // Intentionally a no-op: there is nothing to reload.
    }
}
