<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class MediaScanRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file'                  => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,jfif,jif,webp,bmp,tif,tiff,psd,pdf,mp4,webm,mkv,csv,doc,docx,mp3,ppt,pptx,rtf,txt,wav',
                'max:102400',
                new FileMimeExtensionMatch,
            ],
            'is_image'              => ['nullable', 'boolean'],
            'accepted_extensions'   => ['nullable', 'array', 'max:50'],
            'accepted_extensions.*' => ['string', 'alpha_num:ascii', 'max:10'],
        ];
    }
}
