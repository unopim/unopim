<?php

namespace Webkul\Admin\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

trait RemapsFileArrayErrors
{
    /**
     * Fields the media component posts as `field[]`, whose errors arrive keyed `field.0`.
     *
     * @return list<string>
     */
    abstract protected function fileArrayFields(): array;

    /**
     * Moves `field.0` errors to `field`, where the form renders them.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        $fields = implode('|', array_map('preg_quote', $this->fileArrayFields()));

        $errors = [];

        foreach ($validator->errors()->toArray() as $key => $messages) {
            $field = preg_replace('/^('.$fields.')\.\d+$/', '$1', $key);

            $errors[$field] = array_merge($errors[$field] ?? [], $messages);
        }

        throw ValidationException::withMessages($errors)
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }
}
