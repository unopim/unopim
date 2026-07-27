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
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            $table->unsignedInteger('locale_id');
            $table->foreign('locale_id')->references('id')->on('locales')->restrictOnDelete();
            $table->index('locale_id', 'pubver_locale_idx');

            $table->unsignedInteger('version');

            $table->string('checksum', 64);

            $table->boolean('is_current')->default(false);

            $table->unsignedInteger('current_locale_id')
                ->storedAs('case when is_current then locale_id else null end');

            $table->dateTime('published_at');

            $table->unsignedInteger('published_by_id')->nullable();
            $table->foreign('published_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->index('published_by_id', 'pubver_pubby_idx');

            $table->dateTime('redacted_at')->nullable();
            $table->unsignedInteger('redacted_by_id')->nullable();
            $table->foreign('redacted_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->text('redacted_reason')->nullable();

            $table->timestamps();

            $table->unique(['publication_id', 'locale_id', 'version'], 'pubver_pub_loc_ver_uq');
            $table->unique(['publication_id', 'current_locale_id'], 'pubver_pub_curloc_uq');
            $table->index(['publication_id', 'is_current', 'locale_id'], 'pubver_pub_cur_loc_idx');
            $table->index(['publication_id', 'published_at'], 'pubver_pub_pubat_idx');
            $table->index('published_at', 'pubver_pubat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_versions');
    }
};
