<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_agent_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('provider')->default('openai');
            $table->string('apiUrl');
            $table->string('apiKey');
            $table->string('model')->default('gpt-4');
            $table->json('extras')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_agent_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('systemPrompt')->nullable();
            $table->json('pipeline')->nullable();
            $table->unsignedBigInteger('credentialId');
            $table->integer('maxTokens')->default(4096);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->json('extras')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('credentialId')
                ->references('id')
                ->on('ai_agent_credentials')
                ->onDelete('cascade');
        });

        Schema::create('ai_agent_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agentId')->nullable();
            $table->unsignedBigInteger('credentialId')->nullable();
            $table->text('instruction')->nullable();
            $table->longText('output')->nullable();
            $table->integer('tokensUsed')->default(0);
            $table->integer('executionTimeMs')->default(0);
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->json('extras')->nullable();
            $table->timestamps();

            $table->foreign('agentId')
                ->references('id')
                ->on('ai_agent_agents')
                ->onDelete('set null');

            $table->foreign('credentialId')
                ->references('id')
                ->on('ai_agent_credentials')
                ->onDelete('set null');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_executions');
        Schema::dropIfExists('ai_agent_agents');
        Schema::dropIfExists('ai_agent_credentials');
    }
};
