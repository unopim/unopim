<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

final readonly class TemplateMessage
{
    public function __construct(
        public string $key,
        public array $replace = [],
    ) {}

    public function message(?string $locale = null): string
    {
        return trans('product::app.templates.messages.'.$this->key, $this->replace, $locale);
    }

    public function identity(): string
    {
        return $this->key.'|'.json_encode($this->replace);
    }
}
