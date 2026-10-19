<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\AiAgent\Chat\ResultRendererRegistry;
use Webkul\AiAgent\Http\Requests\ConversationRequest;
use Webkul\AiAgent\Http\Requests\DestroyConversationRequest;
use Webkul\AiAgent\Http\Requests\StoreConversationRequest;
use Webkul\AiAgent\Repositories\ConversationRepository;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversationRepository,
        protected ResultRendererRegistry $resultRenderers,
    ) {}

    /**
     * List the user's saved conversations.
     */
    public function index(ConversationRequest $request): JsonResponse
    {
        return new JsonResponse([
            'conversations' => $this->conversationRepository->latestForUser(auth()->guard('admin')->id()),
        ]);
    }

    /**
     * Show a saved conversation with its latest messages and deletion markers.
     */
    public function show(ConversationRequest $request, int $id): JsonResponse
    {
        $userId = auth()->guard('admin')->id();
        $conversation = $this->conversationRepository->findForUser($id, $userId);

        if (! $conversation) {
            return new JsonResponse(['error' => trans('ai-agent::app.common.conversation-not-found')], 404);
        }

        $limit = $this->retainedMessages();

        $messages = $this->conversationRepository->latestMessages($id, $limit)->map(function ($message) {
            $message->presentation = $this->resultRenderers->descriptor(json_decode($message->presentation ?? 'null', true));

            foreach (json_decode($message->display_data ?? '{}', true) ?: [] as $key => $value) {
                $message->{$key} = $value;
            }

            unset($message->display_data);

            return $message;
        });

        return new JsonResponse([
            'conversation'        => $conversation,
            'messages'            => $messages,
            'deleted_message_ids' => $this->conversationRepository->deletedMessageIds($id, $limit),
        ]);
    }

    /**
     * Merge the widget's conversation snapshot into saved history.
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        $conversationId = $this->conversationRepository->saveSnapshot(
            auth()->guard('admin')->id(),
            [
                'conversation_id'     => $request->input('conversation_id'),
                'title'               => $request->input('title'),
                'metadata'            => $request->input('metadata'),
                'messages'            => $request->input('messages'),
                'deleted_message_ids' => $request->input('deleted_message_ids', []),
            ],
            $this->retainedMessages(),
        );

        return new JsonResponse(['id' => $conversationId, 'saved' => true]);
    }

    /**
     * Delete a saved conversation; allowed even after history is disabled.
     */
    public function destroy(DestroyConversationRequest $request, int $id): JsonResponse
    {
        return new JsonResponse([
            'deleted' => $this->conversationRepository->deleteForUser($id, auth()->guard('admin')->id()),
        ]);
    }

    /**
     * Number of messages, and of deletion markers, kept per conversation.
     */
    protected function retainedMessages(): int
    {
        return max(1, (int) config('ai-agent.history.max_messages', 500));
    }
}
