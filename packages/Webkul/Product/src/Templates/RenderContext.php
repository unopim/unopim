<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final class RenderContext
{
    private array $stack = [];

    private array $rendered = [];

    private array $errors = [];

    private array $warnings = [];

    private int $renderedBytes = 0;

    /**
     * Create a new render context instance.
     */
    public function __construct(
        public readonly array $values,
        public readonly string $channel,
        public readonly string $locale,
    ) {}

    /**
     * Push the attribute code onto the stack of templates being rendered.
     */
    public function enter(string $attributeCode): void
    {
        $this->stack[] = $attributeCode;
    }

    /**
     * Pop the most recent attribute code off the render stack.
     */
    public function leave(): void
    {
        array_pop($this->stack);
    }

    /**
     * Get the current nesting depth of the render stack.
     */
    public function depth(): int
    {
        return count($this->stack);
    }

    /**
     * Determine if the attribute is already being rendered higher up the stack.
     */
    public function isVisiting(string $attributeCode): bool
    {
        return in_array($attributeCode, $this->stack, true);
    }

    /**
     * Get the render path from the current stack to the given attribute code.
     */
    public function pathTo(string $attributeCode): string
    {
        return implode(' → ', [...$this->stack, $attributeCode]);
    }

    /**
     * Determine if the attribute's output has already been rendered and remembered.
     */
    public function hasRendered(string $attributeCode): bool
    {
        return array_key_exists($attributeCode, $this->rendered);
    }

    /**
     * Get the remembered output for the attribute, or null when none is stored.
     */
    public function rendered(string $attributeCode): ?string
    {
        return $this->rendered[$attributeCode] ?? null;
    }

    /**
     * Store the rendered output for the attribute and return it.
     */
    public function remember(string $attributeCode, ?string $output): ?string
    {
        return $this->rendered[$attributeCode] = $output;
    }

    /**
     * Add the given bytes to the rendered output total and return the new total.
     */
    public function consumeOutput(int $bytes): int
    {
        return $this->renderedBytes += $bytes;
    }

    /**
     * Record an error message, ignoring duplicates.
     */
    public function error(TemplateMessage $message): void
    {
        $this->errors[$message->identity()] = $message;
    }

    /**
     * Record a warning message, ignoring duplicates.
     */
    public function warn(TemplateMessage $message): void
    {
        $this->warnings[$message->identity()] = $message;
    }

    /**
     * Get the recorded error messages.
     */
    public function errors(): array
    {
        return array_values($this->errors);
    }

    /**
     * Get the recorded warning messages.
     */
    public function warnings(): array
    {
        return array_values($this->warnings);
    }
}
