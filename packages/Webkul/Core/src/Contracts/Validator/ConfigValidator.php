<?php

declare(strict_types=1);

namespace Webkul\Core\Contracts\Validator;

interface ConfigValidator
{
    public function validate(array $data, array $options = []): array;
}
