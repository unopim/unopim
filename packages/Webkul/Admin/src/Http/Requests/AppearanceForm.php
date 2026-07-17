<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class AppearanceForm extends FormRequest
{
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
        $logoImageRules = $this->imageRules(['bmp', 'jpeg', 'jpg', 'png', 'webp'], 2048);
        $faviconRules = $this->imageRules(['ico', 'png', 'webp'], 1024, false);

        return [
            'logo_image'   => $this->file('logo_image') instanceof UploadedFile ? $logoImageRules : ['nullable'],
            'logo_image.*' => $logoImageRules,
            'favicon'      => $this->file('favicon') instanceof UploadedFile ? $faviconRules : ['nullable'],
            'favicon.*'    => $faviconRules,
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
