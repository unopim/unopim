<?php

namespace Webkul\ProductPassport\DataTransferObjects;

final readonly class PassportPublishRefusal
{
    /**
     * @param  array<int, array{locale_code: string, status: string, missing_fields: array<int, array{code: string, label: string, source: string}>}>  $blockedLocales
     */
    public function __construct(
        public string $field,
        public string $message,
        public array $blockedLocales = [],
    ) {}

    /**
     * Build the 422 response body shared by the admin and API publish endpoints.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'errors'  => [$this->field => [$this->message]],
            ...($this->blockedLocales === [] ? [] : ['blocked_locales' => $this->blockedLocales]),
        ];
    }
}
