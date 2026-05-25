<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Handles persistent conversation storage for the AI chat widget.
 */
class ConversationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! bouncer()->hasPermission('ai-agent.general')) {
                abort(403, trans('ai-agent::app.common.unauthorized'));
            }

            return $next($request);
        });
    }

    /**
     * List conversations for the current user.
     */
    public function index(): JsonResponse
    {
        $userId = auth()->guard('admin')->id();

        $conversations = DB::table('ai_agent_conversations')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'metadata', 'created_at', 'updated_at']);

        return new JsonResponse(['conversations' => $conversations]);
    }

    /**
     * Load messages for a specific conversation.
     */
    public function show(int $id): JsonResponse
    {
        $userId = auth()->guard('admin')->id();

        $conversation = DB::table('ai_agent_conversations')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $conversation) {
            return new JsonResponse(['error' => 'Conversation not found'], 404);
        }

        $messages = DB::table('ai_agent_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->get(['role', 'content', 'tool_calls', 'tokens_used', 'created_at']);

        return new JsonResponse([
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }

    /**
     * Save/sync a conversation from the widget.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'              => 'nullable|string|max:255',
            'messages'           => 'required|array',
            'messages.*.role'    => 'required|in:user,assistant,system',
            'messages.*.content' => 'required|string',
        ]);

        $userId = auth()->guard('admin')->id();

        $conversationId = DB::table('ai_agent_conversations')->insertGetId([
            'user_id'    => $userId,
            'title'      => $request->input('title', 'Chat '.now()->format('M d, H:i')),
            'metadata'   => json_encode($request->input('metadata', [])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $messages = collect($request->input('messages'))->map(fn ($m) => [
            'conversation_id' => $conversationId,
            'role'            => $m['role'],
            'content'         => $m['content'],
            'tool_calls'      => isset($m['tool_calls']) ? json_encode($m['tool_calls']) : null,
            'tokens_used'     => $m['tokens_used'] ?? 0,
            'created_at'      => now(),
        ]);

        DB::table('ai_agent_messages')->insert($messages->toArray());

        return new JsonResponse([
            'id'    => $conversationId,
            'saved' => true,
        ]);
    }

    /**
     * Delete a conversation.
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = auth()->guard('admin')->id();

        $deleted = DB::table('ai_agent_conversations')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return new JsonResponse(['deleted' => (bool) $deleted]);
    }
}
