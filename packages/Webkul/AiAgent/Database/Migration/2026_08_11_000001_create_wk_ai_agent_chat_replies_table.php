<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The chat assistant reply is generated on a queue worker and its text is
     * written here as it streams, so the reply survives a full page reload: the
     * widget polls this row by id and resumes on the next page. Kept separate
     * from ai_agent_conversations (which the widget persists itself).
     */
    public function up(): void
    {
        Schema::create('ai_agent_chat_replies', function (Blueprint $table): void {
            // UUID so the client-polled id is unguessable.
            $table->uuid('id')->primary();
            $table->unsignedInteger('user_id');
            // The client conversation/session id this reply belongs to.
            $table->string('session_id', 191)->nullable();
            $table->enum('status', ['queued', 'generating', 'done', 'error'])->default('queued');
            // Assistant text, appended as it streams.
            $table->longText('content')->nullable();
            // Structured action results (redirects, confirmations, etc.).
            $table->json('actions')->nullable();
            // Live status label shown while generating (thinking / a tool name).
            $table->string('agent_status', 191)->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'session_id']);
            // Supports the TTL cleanup sweep.
            $table->index('created_at');
            $table->foreign('user_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_chat_replies');
    }
};
