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
        Schema::table('attributes', function (Blueprint $table): void {
            $table->json('allowed_extensions')->nullable()->after('regex_pattern');
            $table->unsignedInteger('max_file_size')->nullable()->after('allowed_extensions');
        });

        Schema::table('attribute_translations', function (Blueprint $table): void {
            $table->text('instructions')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropColumn(['allowed_extensions', 'max_file_size']);
        });

        Schema::table('attribute_translations', function (Blueprint $table): void {
            $table->dropColumn('instructions');
        });
    }
};
