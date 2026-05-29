<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\AlphaNumericSpace;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class UserForm extends FormRequest
{
    /**
     * Create a new instance.
     */
    public function __construct(
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->id ?: null;

        return [
            'name'                  => ['required', new AlphaNumericSpace],
            'email'                 => [
                'required',
                'email',
                Rule::unique('admins', 'email')->ignore($id, 'id'),
            ],
            'password'              => 'nullable|min:6',
            'password_confirmation' => 'nullable|required_with:password|same:password',
            'status'                => 'sometimes',
            'ui_locale_id'          => 'required',
            'role_id'               => 'required',
            'timezone'              => 'required',
            'image.*'               => [
                'sometimes',
                'image',
                'mimes:jpeg,png,jpg,svg,gif',
                new FileMimeExtensionMatch,
            ],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     *
     * @throws ValidationException
     */
    #[\Override]
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        if (! isset($errors['image.0'])) {
            parent::failedValidation($validator);

            return;
        }

        $errors['image'] = $errors['image.0'];

        unset($errors['image.0']);

        throw ValidationException::withMessages($errors)
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }
}
