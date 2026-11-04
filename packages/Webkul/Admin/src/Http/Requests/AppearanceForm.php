<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class AppearanceForm extends FormRequest
{
    /**
     * Extensions accepted for the admin logo.
     *
     * @var array<int, string>
     */
    const LOGO_EXTENSIONS = ['bmp', 'jpeg', 'jpg', 'png', 'webp'];

    /**
     * Extensions accepted for the favicon.
     *
     * @var array<int, string>
     */
    const FAVICON_EXTENSIONS = ['ico', 'jpeg', 'jpg', 'png', 'webp'];

    /**
     * Maximum admin logo size in kilobytes.
     */
    const LOGO_MAX_SIZE = 2048;

    /**
     * Maximum favicon size in kilobytes.
     */
    const FAVICON_MAX_SIZE = 1024;

    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        $logoImageRules = $this->imageRules(self::LOGO_EXTENSIONS, self::LOGO_MAX_SIZE);
        $faviconRules = $this->imageRules(self::FAVICON_EXTENSIONS, self::FAVICON_MAX_SIZE, false);

        return [
            'logo_image'   => $this->file('logo_image') instanceof UploadedFile ? $logoImageRules : ['nullable'],
            'logo_image.*' => $logoImageRules,
            'favicon'      => $this->file('favicon') instanceof UploadedFile ? $faviconRules : ['nullable'],
            'favicon.*'    => $faviconRules,
        ];
    }

    /**
     * Get the display names for the uploaded files.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'logo_image.*' => trans('admin::app.settings.appearance.logo'),
            'favicon.*'    => trans('admin::app.settings.appearance.favicon'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * The media component posts files as `field[]`, so errors are moved from `field.0` to `field` where the form renders them.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = [];

        foreach ($validator->errors()->toArray() as $key => $messages) {
            $field = preg_replace('/\.\d+$/', '', $key);

            $errors[$field] = array_merge($errors[$field] ?? [], $messages);
        }

        throw ValidationException::withMessages($errors)
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Build authoritative rules for both scalar and media-component array uploads.
     *
     * @param  array<int, string>  $extensions
     * @return array<int, mixed>
     */
    private function imageRules(array $extensions, int $maxKilobytes, bool $requireImage = true): array
    {
        return [
            'nullable',
            $requireImage ? 'image' : 'file',
            'mimes:'.implode(',', $extensions),
            'max:'.$maxKilobytes,
            new FileMimeExtensionMatch,
        ];
    }
}
