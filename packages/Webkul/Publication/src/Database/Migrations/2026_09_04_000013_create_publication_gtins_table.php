<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Publication\Services\Gs1DigitalLink;

return new class extends Migration
{
    private const BACKFILL_CHUNK = 1000;

    /**
     * Every well-formed GTIN a publication currently carries becomes the first entry of its history. The
     * backfill streams the publications by primary key and inserts one batch per chunk, so memory stays flat
     * on catalogs of any size. `publications.gtin` may hold values outside the GS1 grammar (it is routing
     * metadata), but the history only serves `/01/{gtin}`, so those rows are skipped in PHP, which stays
     * portable across engines.
     *
     * A run that stopped after the table was created (MySQL commits DDL implicitly, so a failed backfill leaves
     * the table behind) is re-run safely: the table is only created when missing, and the backfill skips every
     * publication whose current GTIN already has a history row.
     */
    public function up(): void
    {
        if (! Schema::hasTable('publication_gtins')) {
            $this->createTable();
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_gtins');
    }

    /**
     * Index names are explicit because the auto-generated ones include the table prefix and overrun
     * MySQL's 64-character identifier limit on prefixed installs.
     *
     * `revoked_at` retires a GTIN that was published by mistake: the history fallback skips the row while the
     * row itself stays.
     */
    private function createTable(): void
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
    }

    private function backfill(): void
    {
        $now = now();
        $gs1 = resolve(Gs1DigitalLink::class);

        DB::table('publications')
            ->whereNotNull('gtin')
            ->where('gtin', '!=', '')
            ->whereNotExists(fn ($history) => $history
                ->select(DB::raw(1))
                ->from('publication_gtins')
                ->whereColumn('publication_gtins.publication_id', 'publications.id')
                ->whereColumn('publication_gtins.gtin', 'publications.gtin'))
            ->select(['id', 'gtin', 'last_published_at', 'created_at'])
            ->chunkById(self::BACKFILL_CHUNK, function ($publications) use ($now, $gs1): void {
                DB::table('publication_gtins')->insert($publications
                    ->filter(fn ($publication): bool => $gs1->isWellFormed($publication->gtin))
                    ->map(fn ($publication): array => [
                        'publication_id' => $publication->id,
                        'gtin'           => $publication->gtin,
                        'recorded_at'    => $publication->last_published_at ?? $publication->created_at ?? $now,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ])
                    ->values()
                    ->all());
            });
    }
};
