<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class TinyMCEUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The TinyMCE rich-text editor only ever uploads raster images, so the
     * accepted set is restricted to a real-extension allowlist. SVG and HTML
     * are intentionally excluded (they can carry script), and
     * FileMimeExtensionMatch rejects files whose real MIME does not match the
     * claimed extension, blocking content-type spoofing.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
                new FileMimeExtensionMatch,
            ],
        ];
    }
}
