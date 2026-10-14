<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class ConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('ai-agent.history.enabled', false)
            && bouncer()->hasPermission('ai-agent.general');
    }

    public function rules(): array
    {
        return [];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('ai-agent::app.common.unauthorized'));
    }
}
