<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Validation\Validator;
use Webkul\AiAgent\Chat\MessageDisplay;
use Webkul\AiAgent\Rules\BoundedJson;

class StoreConversationRequest extends ConversationRequest
{
    /**
     * Get the validation rules for a widget conversation snapshot.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'conversation_id'               => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'metadata'                      => ['nullable', 'array', 'max:100', new BoundedJson(65536)],
            'title'                         => ['nullable', 'string', 'max:255'],
            'messages'                      => ['present', 'array', 'max:100'],
            'messages.*'                    => ['array', new BoundedJson(262144)],
            'messages.*.tool_calls'         => ['nullable', 'array', 'max:100', new BoundedJson(65536)],
            'messages.*.tokens_used'        => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'messages.*.request_id'         => ['nullable', 'uuid'],
            'messages.*.message_id'         => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'messages.*.presentation'       => ['nullable', 'array:type,id'],
            'messages.*.presentation.type'  => ['required_with:messages.*.presentation', 'string', 'max:64', 'regex:/^[a-z][a-z0-9.-]*$/D'],
            'messages.*.presentation.id'    => ['required_with:messages.*.presentation', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'messages.*.role'               => ['required', 'in:user,assistant,system'],
            'messages.*.content'            => ['present', 'string', 'max:50000'],
            'deleted_message_ids'           => ['sometimes', 'array', 'max:100'],
            'deleted_message_ids.*'         => ['required', 'string', 'max:64', 'distinct', 'regex:/^[A-Za-z0-9_-]+$/D'],
        ];
    }

    /**
     * Validate each message's display fields once the shape rules pass.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('messages', []) as $message) {
                app(MessageDisplay::class)->normalize($message);
            }
        }];
    }
}
