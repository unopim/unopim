<?php

namespace Webkul\Webhook\Http\Requests;

use Webkul\Admin\Http\Requests\SelectableMassDestroyRequest;

class LogsMassDestroyRequest extends SelectableMassDestroyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'webhook_id' => ['sometimes', 'integer'],
        ]);
    }

    /**
     * Carry the webhook a scoped logs grid is pinned to, so a queued select-all resolves the same rows.
     *
     * @return array<string, mixed>
     */
    public function selectionParams(): array
    {
        return array_merge(parent::selectionParams(), $this->only('webhook_id'));
    }
}
