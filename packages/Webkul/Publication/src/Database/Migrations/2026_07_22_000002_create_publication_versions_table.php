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
            // A version row can only ever be destroyed by cascading from its
            // parent publication, and Publication::deleting() refuses that
            // whenever any version exists — so in practice this FK never
            // fires. RESTRICT (not CASCADE) makes that guarantee hold even if
            // the app-layer guard is ever bypassed via a raw query.
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            // Retention obligation outlives the catalog record: a locale cannot be
            // deleted while attested versions still exist in that language.
            // Declared explicitly: MySQL auto-indexes FK columns, PostgreSQL does not.
            $table->unsignedInteger('locale_id');
            $table->foreign('locale_id')->references('id')->on('locales')->restrictOnDelete();
            $table->index('locale_id');

            $table->unsignedInteger('version');

            // The public payload is now stored in `publication_version_payloads`
            // (gzip-compressed, external to this table) so this clustered index
            // stays thin at 500K products × 24 locales × repeated revisions over
            // a ten-year retention window. See PublicationVersion::payload().

            $table->string('checksum', 64);

            $table->boolean('is_current')->default(false);

            // Generated column + unique index is how "at most one current
            // version per (publication, locale)" is enforced at the database
            // layer: MySQL/Postgres both treat NULL as distinct for unique
            // indexes, so non-current rows (current_locale_id = NULL) never
            // collide, while two rows flagged current for the same locale do.
            $table->unsignedInteger('current_locale_id')
                ->storedAs('case when is_current = 1 then locale_id else null end');

            // A `timestamp` stops at 2038-01-19; this table carries a ten-year
            // retention obligation and code always sets this on write.
            $table->dateTime('published_at');

            $table->unsignedInteger('published_by_id')->nullable();
            $table->foreign('published_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->index('published_by_id');

            // GDPR Art. 17: the one sanctioned exception to immutability. See
            // PublicationVersion::redact() — payload is nulled, checksum is kept
            // so the audit trail still proves what was removed, and the
            // transition is one-way (redacted_at null -> set, never back).
            $table->dateTime('redacted_at')->nullable();
            $table->unsignedInteger('redacted_by_id')->nullable();
            $table->foreign('redacted_by_id')->references('id')->on('admins')->nullOnDelete();
            $table->text('redacted_reason')->nullable();

            $table->timestamps();

            $table->unique(['publication_id', 'locale_id', 'version']);
            $table->unique(['publication_id', 'current_locale_id']);
            // (publication_id, is_current, locale_id): is_current before locale_id
            // because every hot-path lookup filters by is_current and is either
            // scoped to one locale or none, never the reverse.
            $table->index(['publication_id', 'is_current', 'locale_id']);
            $table->index(['publication_id', 'published_at']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_versions');
    }
};
