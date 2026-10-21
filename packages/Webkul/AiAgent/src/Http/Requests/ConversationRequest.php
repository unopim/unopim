<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ConversationRequest extends FormRequest
{
    /**
     * Saved history must be enabled and the user must hold the chat ACL.
     */
    public function authorize(): bool
    {
        return (bool) config('ai-agent.history.enabled', false)
            && bouncer()->hasPermission('ai-agent.general');
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

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('ai-agent::app.common.unauthorized'));
    }
}
