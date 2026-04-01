<?php

namespace Webkul\MagicAI\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Contracts\Validator\ConfigValidator;

class MagicAICredentialValidator implements ConfigValidator
{
    public function validate(array $credentials, array $options = []): array
    {
        $credentials = $credentials['general']['magic_ai']['settings'];

        $rules = [
            'enabled' => 'required|in:0,1',
        ];

        $validator = Validator::make($credentials, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [];
    }
}
