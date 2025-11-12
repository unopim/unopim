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
        Schema::create('magic_ai_system_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('tone');
            $table->integer('max_tokens');
            $table->float('temperature');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_ai_system_prompts');
    }
};
