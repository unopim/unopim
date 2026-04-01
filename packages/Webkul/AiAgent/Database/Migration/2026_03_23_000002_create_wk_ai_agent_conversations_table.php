<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Persistent conversation storage
        Schema::create('ai_agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('title', 255)->default('New conversation');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('admins')->cascadeOnDelete();
        });

        // Individual messages within conversations
        Schema::create('ai_agent_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->json('tool_calls')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('ai_agent_conversations')->cascadeOnDelete();
        });

        // Agent memory — persistent facts the agent learns
        Schema::create('ai_agent_memories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->enum('scope', ['user', 'product', 'catalog', 'global']);
            $table->string('key', 255);
            $table->text('value');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'key']);
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('admins')->nullOnDelete();
        });

        // Changeset tracking for rollback capability
        Schema::create('ai_agent_changesets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('description', 500);
            $table->json('changes');
            $table->enum('status', ['pending', 'applied', 'rolled_back'])->default('applied');
            $table->unsignedInteger('affected_count')->default(0);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->foreign('user_id')->references('id')->on('admins')->nullOnDelete();
        });

        // Background task queue for autonomous agents
        Schema::create('ai_agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'paused'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->json('config')->nullable();
            $table->json('result')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('type');
            $table->foreign('parent_task_id')->references('id')->on('ai_agent_tasks')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_tasks');
        Schema::dropIfExists('ai_agent_changesets');
        Schema::dropIfExists('ai_agent_memories');
        Schema::dropIfExists('ai_agent_messages');
        Schema::dropIfExists('ai_agent_conversations');
    }
};
