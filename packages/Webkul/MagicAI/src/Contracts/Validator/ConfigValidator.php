<?php
namespace Webkul\MagicAI\Contracts\Validator;

interface ConfigValidator
{
    public function validate(array $data, array $options = []): array;
}
