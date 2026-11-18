<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final class TemplateDependencyGraph
{
    private array $finished = [];

    private array $cycles = [];

    /**
     * Create a new dependency graph instance from the attribute references.
     */
    public function __construct(private readonly array $references) {}

    /**
     * Find every circular reference path in the graph.
     */
    public function cycles(): array
    {
        $this->finished = [];
        $this->cycles = [];

        foreach (array_keys($this->references) as $node) {
            $this->visit((string) $node, []);
        }

        return $this->cycles;
    }

    private function visit(string $node, array $path): void
    {
        if (isset($this->finished[$node])) {
            return;
        }

        $position = array_search($node, $path, true);

        if ($position !== false) {
            $this->cycles[] = [...array_slice($path, $position), $node];

            return;
        }

        foreach ($this->references[$node] ?? [] as $reference) {
            if (array_key_exists($reference, $this->references)) {
                $this->visit($reference, [...$path, $node]);
            }
        }

        $this->finished[$node] = true;
    }
}
