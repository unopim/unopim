<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKFILL_CHUNK = 1000;

    /**
     * Index names are explicit because the auto-generated ones include the table prefix and overrun
     * MySQL's 64-character identifier limit on prefixed installs.
     *
     * `revoked_at` retires a GTIN that was published by mistake: the history fallback skips the row while the
     * row itself stays.
     *
     * Every GTIN a publication currently carries becomes the first entry of its history. The backfill
     * streams the publications by primary key and inserts one batch per chunk, so memory stays flat on
     * catalogs of any size.
     */
    public function up(): void
    {
        Schema::create('publication_gtins', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            $table->string('gtin', 14);

            $table->dateTime('recorded_at');

            $table->dateTime('revoked_at')->nullable();

            $table->timestamps();

            $table->unique(['publication_id', 'gtin'], 'pubgtin_pub_gtin_uq');
            $table->index('gtin', 'pubgtin_gtin_idx');
        });

        $now = now();

        DB::table('publications')
            ->whereNotNull('gtin')
            ->where('gtin', '!=', '')
            ->select(['id', 'gtin', 'last_published_at', 'created_at'])
            ->chunkById(self::BACKFILL_CHUNK, function ($publications) use ($now): void {
                DB::table('publication_gtins')->insert($publications->map(fn ($publication): array => [
                    'publication_id' => $publication->id,
                    'gtin'           => $publication->gtin,
                    'recorded_at'    => $publication->last_published_at ?? $publication->created_at ?? $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_gtins');
    }
};
