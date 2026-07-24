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
            // Derived 1:1 storage, so cascadeOnDelete is correct here.
            $table->unsignedBigInteger('publication_version_id')->primary();
            $table->foreign('publication_version_id', 'pubverpay_version_fk')->references('id')->on('publication_versions')->cascadeOnDelete();

            // gzip-9 JSON; nullable because redaction nulls it.
            $table->binary('payload')->nullable();

            $table->string('archive_path')->nullable();

            $table->timestamps();
        });

        // BLOB caps at 64 KB; MEDIUMBLOB gives 16 MB. Raw statement, so it must
        // honour the table prefix. MySQL only — Postgres bytea is untiered.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $table = Schema::getConnection()->getTablePrefix().'publication_version_payloads';

            DB::statement('ALTER TABLE `'.$table.'` MODIFY payload MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_version_payloads');
    }
};
