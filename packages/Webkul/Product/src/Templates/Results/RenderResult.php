<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Results;

final readonly class RenderResult
{
    use InteractsWithMessages;

    /**
     * Create a new render result instance.
     */
    public function __construct(
        public string $source,
        public string $output,
        public array $errors = [],
        public array $warnings = [],
    ) {}

    /**
     * Create a result whose output is the unchanged source.
     */
    public static function unchanged(string $source): self
    {
        return new self($source, $source);
    }
}
