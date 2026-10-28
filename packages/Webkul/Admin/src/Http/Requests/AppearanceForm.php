<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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
        $logoImageRules = $this->imageRules(self::LOGO_EXTENSIONS, 2048);
        $faviconRules = $this->imageRules(self::FAVICON_EXTENSIONS, 1024, false);

        return [
            ...$this->uploadRules('logo_image', $logoImageRules),
            ...$this->uploadRules('logo_image_dark', $logoImageRules),
            ...$this->uploadRules('favicon', $faviconRules),
            ...$this->uploadRules('favicon_dark', $faviconRules),
        ];
    }

    /**
     * Apply the image rules to a field sent either as a scalar file or as a media-component array.
     *
     * @param  array<int, mixed>  $rules
     * @return array<string, array<int, mixed>>
     */
    private function uploadRules(string $field, array $rules): array
    {
        return [
            $field      => $this->file($field) instanceof UploadedFile ? $rules : ['nullable'],
            $field.'.*' => $rules,
        ];
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
