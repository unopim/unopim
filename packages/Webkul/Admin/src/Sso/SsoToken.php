<?php

namespace Webkul\Admin\Sso;

final readonly class SsoToken
{
    public function __construct(
        public string $accessToken,
        public ?string $idToken = null,
    ) {}
}
