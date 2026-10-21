<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `sequence` is monotonic per publication: one number identifies one publish moment across every locale.
     *
     * Index names are explicit because the auto-generated ones include the table prefix and overrun MySQL's
     * 64-character identifier limit on prefixed installs.
     */
    public function up(): void
    {
        Schema::create('publication_releases', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            $table->unsignedInteger('sequence');

            $table->dateTime('published_at');

            $table->unsignedInteger('published_by_id')->nullable();
            $table->foreign('published_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->index('published_by_id', 'pubrel_pubby_idx');

            $table->timestamps();

            $table->unique(['publication_id', 'sequence'], 'pubrel_pub_seq_uq');
            $table->index(['publication_id', 'published_at'], 'pubrel_pub_pubat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_releases');
    }
};
