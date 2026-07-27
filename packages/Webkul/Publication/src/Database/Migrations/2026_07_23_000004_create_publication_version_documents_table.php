<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_version_documents', function (Blueprint $table): void {
            $table->id();

            // Derived index, not attested content — safe to prune and rebuild
            // on every publish/redaction, unlike the version row it points at.
            $table->unsignedBigInteger('publication_version_id');
            $table->foreign('publication_version_id', 'pvd_version_fk')
                ->references('id')->on('publication_versions')->cascadeOnDelete();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id', 'pvd_publication_fk')
                ->references('id')->on('publications')->cascadeOnDelete();

            $table->string('path');

            $table->timestamps();

            // Explicit names: auto names include the table prefix and overrun
            // MySQL's 64-char identifier limit on prefixed installs.
            $table->unique(['publication_version_id', 'path'], 'pvd_version_path_uq');
            // The one indexed lookup the public asset controller runs on every request.
            $table->index(['publication_id', 'path'], 'pvd_pub_path_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_version_documents');
    }
};
