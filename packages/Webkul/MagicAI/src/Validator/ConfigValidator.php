<?php

namespace Webkul\MagicAI\Validator;

use Webkul\MagicAI\Contracts\Validator\ConfigValidator as ConfigValidatorContract;

class ConfigValidator implements ConfigValidatorContract
{
    public function validate(array $credentials, array $options = []): array
    {

        return $this->validate($credentials); 
    }
}
