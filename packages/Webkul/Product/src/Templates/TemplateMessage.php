<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class TemplateMessage
{
    /**
     * Create a new template message instance.
     */
    public function __construct(
        public string $key,
        public array $replace = [],
    ) {}

    /**
     * Get the translated message text for the given locale.
     */
    public function message(?string $locale = null): string
    {
        return trans('product::app.templates.messages.'.$this->key, $this->replace, $locale);
    }

    /**
     * Get a unique key built from the message key and its replacements.
     */
    public function identity(): string
    {
        return $this->key.'|'.json_encode($this->replace);
    }
}
