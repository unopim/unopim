<?php

namespace Webkul\Admin\Exceptions;

use Exception;

class SsoAuthenticationException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $flashType = 'error',
        public readonly ?string $email = null,
    ) {
        parent::__construct($message);
    }

    public static function rejected(?string $email = null): self
    {
        return new self(trans('admin::app.settings.users.login-error'), 'error', $email);
    }

    public static function inactive(?string $email = null): self
    {
        return new self(trans('admin::app.settings.users.activate-warning'), 'warning', $email);
    }
}
