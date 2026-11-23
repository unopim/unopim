<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class DownloadExportRequest extends FormRequest
{
    /**
     * Only a user who may run exports can fetch the file an AI export produced.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('data_transfer.export.execute');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Answer a denied download with the AI agent's unauthorized message.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('ai-agent::app.common.unauthorized'));
    }
}
