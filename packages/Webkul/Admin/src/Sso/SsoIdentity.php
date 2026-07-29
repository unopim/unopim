<?php

namespace Webkul\Admin\Sso;

final readonly class SsoIdentity
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $email,
        public ?string $name = null,
        public array $raw = [],
    ) {}
}
