<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class AccountForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = auth()->guard('admin')->user();

        $this->merge([
            'timezone'     => $this->input('timezone') ?: ($user?->timezone ?: config('app.timezone', 'UTC')),
            'ui_locale_id' => $this->input('ui_locale_id') ?: $user?->ui_locale_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = auth()->guard('admin')->user();

        $passwordMin = config('admin.auth.password_min');

        if (! is_numeric($passwordMin)) {
            $passwordMin = 8;
        }

        $imageRules = [
            'nullable',
            'image',
            'mimes:'.implode(',', UserForm::PROFILE_IMAGE_EXTENSIONS),
            'max:2048',
            new FileMimeExtensionMatch,
        ];

        return [
            'name'               => 'required',
            'email'              => ['email', Rule::unique('admins', 'email')->ignore($user?->id, 'id')],
            'password'           => 'nullable|confirmed|min:'.$passwordMin,
            'current_password'   => Rule::when(
                $this->filled('password'),
                ['required', 'current_password:admin'],
                ['nullable'],
            ),
            'image'              => $this->file('image') instanceof UploadedFile ? $imageRules : ['nullable'],
            'image.*'            => $imageRules,
            'timezone'           => 'required',
            'ui_locale_id'       => 'required',
            'catalog_locale_id'  => 'nullable|integer|exists:locales,id,status,1',
            'default_channel_id' => 'nullable|integer|exists:channels,id',
            'use_gravatar'       => 'boolean',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $password = (string) $this->input('password');

                if ($password !== '' && trim($password) === '') {
                    $validator->errors()->add('password', trans('admin::app.account.edit.password-whitespace'));
                }
            },
        ];
    }
}
