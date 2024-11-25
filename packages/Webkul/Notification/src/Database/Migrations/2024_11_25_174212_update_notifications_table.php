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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('url')->nullable()->after('type');
            $table->string('title')->nullable()->after('url');
            $table->text('description')->nullable()->after('title');
            $table->json('context')->nullable()->after('description');
            $table->dropColumn(['read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('read')->default(0);
            $table->dropColumn(['url', 'title', 'description', 'context']); // Drop the columns if rolling back
        });
    }
};
