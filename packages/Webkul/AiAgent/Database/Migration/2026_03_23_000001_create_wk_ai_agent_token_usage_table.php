<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_token_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->date('usage_date');
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'usage_date']);
            $table->index('usage_date');

            $table->foreign('user_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_token_usage');
    }
};
