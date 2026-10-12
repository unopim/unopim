<?php

namespace Webkul\Installer\Helpers\Upgrade;

/**
 * Outcome of a single upgrade check.
 *
 * `$remedy` carries the operator-facing instruction for anything that is not a
 * pass, so the console layer never has to map a check name back to advice.
 */
class CheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly CheckStatus $status,
        public readonly string $detail = '',
        public readonly string $remedy = '',
    ) {}

    public static function passed(string $name, string $detail = ''): self
    {
        return new self($name, CheckStatus::Passed, $detail);
    }

    public static function warning(string $name, string $detail = '', string $remedy = ''): self
    {
        return new self($name, CheckStatus::Warning, $detail, $remedy);
    }

    public static function failed(string $name, string $detail = '', string $remedy = ''): self
    {
        return new self($name, CheckStatus::Failed, $detail, $remedy);
    }

    public function isFailure(): bool
    {
        return $this->status === CheckStatus::Failed;
    }
}
