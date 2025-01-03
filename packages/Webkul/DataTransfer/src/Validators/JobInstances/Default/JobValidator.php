<?php

namespace Webkul\DataTransfer\Validators\JobInstances\Default;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Contracts\Validator\JobInstances\JobValidator as JobValidatorContract;

/**
 * Class JobValidator
 *
 * This class is responsible for validating job instance data
 * according to specified rules, custom messages, and attribute names.
 *
 * Can be extended to implement custom validate function while using the other helper functions of this class
 */
class JobValidator implements JobValidatorContract
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [];

    /**
     * Custom error messages for validation
     */
    protected array $messages = [];

    /**
     * Names to be used for attributes during generation of error message
     */
    protected array $attributeNames = [];

    /**
     * Validates the data
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): void
    {
        $data = $this->preValidationProcess($data);

        $validator = Validator::make($data, $this->getRules($options), $this->getMessages($options), $this->getAttributeNames($options));

        if ($validator->fails()) {
            $messages = $this->processErrorMessages($validator);

            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * Validation rules for job instance
     */
    public function getRules(array $options): array
    {
        return $this->rules;
    }

    /**
     * Custom names for validation attributes
     */
    public function getAttributeNames(array $options): array
    {
        return $this->attributeNames;
    }

    /**
     * Add Custom error messages for validation
     */
    public function getMessages(array $options): array
    {
        return $this->messages;
    }

    /**
     * Process data before validation
     */
    public function preValidationProcess(mixed $data): mixed
    {
        return $data;
    }

    /**
     * Process error messages for array input fields
     */
    protected function processErrorMessages(ValidatorContract $validator): array
    {
        $messages = [];

        foreach ($validator->errors()->messages() as $key => $message) {
            $messageKey = str_contains($key, '.') ? str_replace('.', '[', $key).']' : $key;

            $messages[$messageKey] = $message;
        }

        return $messages;
    }
}
