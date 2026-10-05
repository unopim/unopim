<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Number;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent');
    }

    /**
     * Get the validation rules.
     *
     * Files skip FileMimeExtensionMatch: finfo reports most CSVs as text/plain,
     * which it rejects, so the closure pins the client extension instead.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message'     => ['required_without_all:images,files', 'nullable', 'string', 'max:50000'],
            'images'      => ['nullable', 'array', 'max:5'],
            'images.*'    => ['image', 'mimes:jpeg,png,webp,gif', 'max:10240'],
            'files'       => ['nullable', 'array', 'max:3'],
            'files.*'     => ['file', 'mimes:csv,xls,xlsx,txt', 'max:102400', function (string $attribute, mixed $value, \Closure $fail): void {
                $allowed = ['csv', 'xlsx', 'xls'];
                $extension = strtolower((string) $value->getClientOriginalExtension());

                if (! in_array($extension, $allowed, true)) {
                    $fail(trans('ai-agent::app.common.invalid-file-type', ['types' => implode(', ', $allowed)]));
                }
            }],
            'platform_id'     => ['nullable', 'integer'],
            'model'           => ['nullable', 'string', 'max:200'],
            'context'         => ['nullable', 'array'],
            'history'         => ['nullable', 'array'],
            'conversation_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    /**
     * Custom messages.
     *
     * PHP discards a file larger than upload_max_filesize before Laravel sees it,
     * so the "uploaded" rule is the one that fails; name the server limit instead
     * of Laravel's bare "failed to upload".
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $serverLimit = trans('ai-agent::app.common.upload-exceeds-server-limit', [
            'size' => Number::fileSize(UploadedFile::getMaxFilesize()),
        ]);

        return [
            'images.*.uploaded' => $serverLimit,
            'files.*.uploaded'  => $serverLimit,
        ];
    }

    /**
     * The widget's id for the conversation this message belongs to.
     */
    public function conversationId(): ?string
    {
        return $this->filled('conversation_id') ? (string) $this->input('conversation_id') : null;
    }

    /**
     * FormData sends the history as a JSON string.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('history'))) {
            $this->merge(['history' => json_decode($this->input('history'), true) ?: []]);
        }
    }
}
