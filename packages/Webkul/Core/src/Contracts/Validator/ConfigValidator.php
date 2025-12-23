<?php

namespace Webkul\Core\Contracts\Validator;

interface ConfigValidator
{
    public function validate(array $data, array $options = []): array;
}
