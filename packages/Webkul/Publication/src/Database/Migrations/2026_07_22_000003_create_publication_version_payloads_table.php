<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_version_payloads', function (Blueprint $table): void {
            // 1:1 derived storage, not attested content in its own right — the
            // checksum that actually attests the content lives on
            // publication_versions. cascadeOnDelete is correct here (unlike on
            // publication_versions itself): if the parent version is ever
            // removed there is nothing left worth keeping.
            $table->unsignedBigInteger('publication_version_id')->primary();
            // Explicit short FK name: the auto name is derived from the
            // *prefixed* table name and overruns MySQL's 64-char identifier
            // limit on prefixed installs. Explicit names are not prefixed.
            $table->foreign('publication_version_id', 'pubverpay_version_fk')->references('id')->on('publication_versions')->cascadeOnDelete();

            // gzip-9 compressed JSON (~4.9x measured ratio). Nullable because
            // redaction (PublicationVersion::redact()) nulls this column while
            // the parent version's checksum is kept as proof of what was removed.
            $table->binary('payload')->nullable();

            // Reserved for a future cold-storage migration (e.g. S3/glacier);
            // nothing writes it yet.
            $table->string('archive_path')->nullable();

            $table->timestamps();
        });

        // Laravel's schema builder has no size-tiered binary helper. MySQL's
        // plain BLOB caps at 64 KB; MEDIUMBLOB (16 MB) is cheap headroom on a
        // column that can never be deleted. Postgres' bytea has no such tiers,
        // so this only applies to MySQL.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Prefix the table name explicitly: a raw statement bypasses the
            // schema builder, so it must honour the install's table prefix or
            // it targets a nonexistent table on any prefixed install.
            $table = Schema::getConnection()->getTablePrefix().'publication_version_payloads';

            DB::statement('ALTER TABLE `'.$table.'` MODIFY payload MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_version_payloads');
    }
};
