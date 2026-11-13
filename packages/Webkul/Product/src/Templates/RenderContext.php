<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final class RenderContext
{
    private array $stack = [];

    private array $rendered = [];

    private array $errors = [];

    private array $warnings = [];

    public function __construct(
        public readonly array $values,
        public readonly string $channel,
        public readonly string $locale,
    ) {}

    public function enter(string $attributeCode): void
    {
        $this->stack[] = $attributeCode;
    }

    public function leave(): void
    {
        array_pop($this->stack);
    }

    public function depth(): int
    {
        return count($this->stack);
    }

    public function isVisiting(string $attributeCode): bool
    {
        return in_array($attributeCode, $this->stack, true);
    }

    public function pathTo(string $attributeCode): string
    {
        return implode(' → ', [...$this->stack, $attributeCode]);
    }

    public function hasRendered(string $attributeCode): bool
    {
        return array_key_exists($attributeCode, $this->rendered);
    }

    public function rendered(string $attributeCode): ?string
    {
        return $this->rendered[$attributeCode] ?? null;
    }

    public function remember(string $attributeCode, ?string $output): ?string
    {
        return $this->rendered[$attributeCode] = $output;
    }

    public function error(TemplateMessage $message): void
    {
        $this->errors[$message->identity()] = $message;
    }

    public function warn(TemplateMessage $message): void
    {
        $this->warnings[$message->identity()] = $message;
    }

    public function errors(): array
    {
        return array_values($this->errors);
    }

    public function warnings(): array
    {
        return array_values($this->warnings);
    }
}
