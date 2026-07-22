<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_versions', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->cascadeOnDelete();

            $table->unsignedInteger('locale_id');
            $table->foreign('locale_id')->references('id')->on('locales')->cascadeOnDelete();

            $table->unsignedInteger('version');

            $table->json('payload');

            $table->string('checksum', 64);

            $table->boolean('is_current')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('published_by_id')->nullable();
            $table->foreign('published_by_id')->references('id')->on('admins')->nullOnDelete();

            $table->timestamps();

            $table->unique(['publication_id', 'locale_id', 'version']);
            $table->index(['publication_id', 'locale_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_versions');
    }
};
