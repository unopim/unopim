<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Results;

use Webkul\Product\Templates\TemplateMessage;

trait InteractsWithMessages
{
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function errorMessages(?string $locale = null): array
    {
        return $this->translate($this->errors, $locale);
    }

    public function warningMessages(?string $locale = null): array
    {
        return $this->translate($this->warnings, $locale);
    }

    private function translate(array $messages, ?string $locale): array
    {
        return array_map(fn (TemplateMessage $message): string => $message->message($locale), $messages);
    }
}
