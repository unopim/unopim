<?php

namespace Webkul\Admin\Sso;

final readonly class SsoIdentity
{
    /**
     * @param  string  $identifier  Provider-side immutable subject id. Email is mutable and
     *                              can be reassigned to a different person, so it is only
     *                              used to link an account the first time.
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $identifier,
        public string $email,
        public ?string $name = null,
        public array $raw = [],
    ) {}
}
