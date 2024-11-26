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
            $table->string('route')->nullable()->after('type');
            $table->json('route_params')->nullable()->after('route');
            $table->string('title')->nullable()->after('route_params');
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
            $table->dropColumn(['route', 'route_params', 'title', 'description', 'context']); // Drop the columns if rolling back
        });
    }
};
