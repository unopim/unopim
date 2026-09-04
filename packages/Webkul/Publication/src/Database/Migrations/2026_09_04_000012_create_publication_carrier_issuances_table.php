<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_carrier_issuances', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            $table->unsignedBigInteger('release_id');
            $table->foreign('release_id')->references('id')->on('publication_releases')->restrictOnDelete();
            $table->index('release_id', 'pubcar_release_idx');

            // The exact string encoded into the carrier, kept verbatim: a later change to the base URL must not rewrite history.
            $table->text('target');

            $table->string('format', 16)->default('svg');

            $table->dateTime('issued_at');

            $table->unsignedInteger('issued_by_id')->nullable();
            $table->foreign('issued_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->index('issued_by_id', 'pubcar_issby_idx');

            $table->timestamps();

            // Explicit names: auto names include the prefix and overrun MySQL's 64-char identifier limit on prefixed installs.
            $table->index(['publication_id', 'issued_at'], 'pubcar_pub_issued_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_carrier_issuances');
    }
};
