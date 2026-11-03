<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assistant replies generated on a queue worker.
     *
     * The text is written here as it streams, so a reply survives a full page
     * reload: the widget polls the row by its unguessable UUID and resumes on
     * the next page. This is transport state, separate from the saved
     * conversation history; `ai-agent:cleanup` prunes old rows by created_at.
     */
    public function up(): void
    {
        Schema::create('ai_agent_chat_replies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('user_id');
            $table->string('conversation_id', 64)->nullable();
            $table->enum('status', ['queued', 'generating', 'done', 'error', 'cancelled'])->default('queued');
            $table->longText('content')->nullable();
            $table->json('actions')->nullable();
            $table->string('agent_status', 191)->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'conversation_id']);
            $table->index('created_at');
            $table->foreign('user_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }

    /**
     * Drop the replies table.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_chat_replies');
    }
};
