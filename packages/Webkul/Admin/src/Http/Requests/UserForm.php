<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\AlphaNumericSpace;

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
        return [
            'name'                  => ['required', new AlphaNumericSpace],
            'email'                 => 'required|email|unique:admins,email,'.$this->id,
            'password'              => 'nullable',
            'password_confirmation' => 'nullable|required_with:password|same:password',
            'status'                => 'sometimes',
            'ui_locale_id'          => 'required',
            'role_id'               => 'required',
            'timezone'              => 'required',
        ];
    }
}
