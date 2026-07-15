<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\AlphaNumericSpace;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class UserForm extends FormRequest
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->id ?: null;
        $passwordMin = config('admin.auth.password_min');

        if (! is_numeric($passwordMin)) {
            $passwordMin = 8;
        }

        return [
            'name'                  => ['required', new AlphaNumericSpace],
            'email'                 => [
                'required',
                'email',
                Rule::unique('admins', 'email')->ignore($id, 'id'),
            ],
            'password'              => sprintf('%s|min:%s', $id ? 'nullable' : 'required', $passwordMin),
            'password_confirmation' => 'nullable|required_with:password|same:password',
            'status'                => 'sometimes',
            'ui_locale_id'          => 'required',
            'catalog_locale_id'     => 'nullable|integer|exists:locales,id,status,1',
            'default_channel_id'    => 'nullable|integer|exists:channels,id',
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

    protected function prepareForValidation(): void
    {
        if ($this->id) {
            return;
        }

        $defaultLocaleId = $this->defaultLocaleId();

        $this->merge([
            'timezone'           => $this->input('timezone') ?: config('app.timezone', 'UTC'),
            'ui_locale_id'       => $this->input('ui_locale_id') ?: $defaultLocaleId,
            'catalog_locale_id'  => $this->input('catalog_locale_id') ?: $defaultLocaleId,
            'default_channel_id' => $this->input('default_channel_id') ?: core()->getDefaultChannel()?->id,
        ]);
    }

    private function defaultLocaleId(): ?int
    {
        $localeId = DB::table('locales')
            ->where('code', core()->getDefaultLocaleCodeFromDefaultChannel())
            ->where('status', 1)
            ->value('id')
            ?? DB::table('locales')
                ->where('status', 1)
                ->value('id');

        if (is_int($localeId)) {
            return $localeId;
        }

        if (is_string($localeId) && ctype_digit($localeId)) {
            return (int) $localeId;
        }

        return null;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
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
