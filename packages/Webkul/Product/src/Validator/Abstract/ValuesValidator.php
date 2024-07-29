<?php

namespace Webkul\Product\Validator\Abstract;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class ValuesValidator
{
    /**
     * Validates the locale wise data and according to attribute value rules
     *
     * @throws ValidationException
     */
    public function validate(mixed $data, array $options = [], ?string $id = null): void
    {
        $rules = $this->generateRules($data, $id, $options);

        $validator = Validator::make($data, $rules, $this->getMessages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validation rules to be used on the data
     */
    abstract protected function generateRules(mixed $data, ?string $productId, array $options);

    /**
     * Get validation messages for the validator
     */
    protected function getMessages()
    {
        return [];
    }
}
