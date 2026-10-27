<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Off, so the per-chunk transactions below are the only ones. PostgreSQL supports transactional DDL, so the
     * migrator would otherwise wrap the whole of up() in one transaction there: a failure halfway would roll
     * every committed chunk back with it, and the chunks would only be savepoints holding their locks to the end.
     * The price is that the schema steps are no longer atomic on PostgreSQL either; see addReleaseColumn().
     *
     * @var bool
     */
    public $withinTransaction = false;

    /**
     * Versions per backfill chunk. Public so a test can push a handful of versions across a chunk boundary.
     */
    public int $chunkSize = 1000;

    /**
     * Backfill: every existing version becomes its own release, numbered per publication in the order the
     * versions were minted, so history reads the same way as anything minted from now on.
     *
     * Mint order is the primary key, not published_at: versions are inserted under the publication row lock,
     * so within a publication their ids grow in exactly the order they were minted, while published_at has
     * second precision (two versions minted in the same second tie) and follows the clock of whichever worker
     * minted them. The id is also the order chunkById walks in, which is what lets the per-publication counter
     * carry across chunks.
     *
     * The versions are walked by primary key in chunks of $chunkSize, one transaction per chunk, on every
     * driver ($withinTransaction is off). The running sequences are passed by reference into each chunk's
     * transaction (an arrow function would capture a copy), so a publication's highest sequence is looked up
     * once per run, not once per chunk. Only versions without a release are visited and each publication's
     * counter starts at its highest existing sequence, so a run that stopped halfway continues cleanly instead
     * of colliding with its own releases.
     */
    public function up(): void
    {
        $this->addReleaseColumn();

        $sequences = [];

        DB::table('publication_versions')
            ->select('id', 'publication_id', 'published_at', 'published_by_id')
            ->whereNull('release_id')
            ->chunkById($this->chunkSize, function (Collection $versions) use (&$sequences): void {
                DB::transaction(function () use ($versions, &$sequences): void {
                    $this->backfillChunk($versions, $sequences);
                });
            });
    }

    /**
     * The column, its foreign key and its index are each added only when missing, in three steps. No driver
     * runs them atomically here (MySQL commits every DDL statement implicitly, and PostgreSQL runs without the
     * wrapping transaction), so a run that stopped after adding the column must still get the foreign key and
     * the index on the rerun instead of being skipped as a whole.
     */
    private function addReleaseColumn(): void
    {
        if (! Schema::hasColumn('publication_versions', 'release_id')) {
            Schema::table('publication_versions', function (Blueprint $table): void {
                $table->unsignedBigInteger('release_id')->nullable()->after('checksum');
            });
        }

        if (! Schema::hasForeignKey('publication_versions', ['release_id'])) {
            Schema::table('publication_versions', function (Blueprint $table): void {
                $table->foreign('release_id')->references('id')->on('publication_releases')->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('publication_versions', 'pubver_release_idx')) {
            Schema::table('publication_versions', function (Blueprint $table): void {
                $table->index('release_id', 'pubver_release_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('publication_versions', function (Blueprint $table): void {
            $table->dropForeign(['release_id']);
            $table->dropIndex('pubver_release_idx');
            $table->dropColumn('release_id');
        });
    }

    /**
     * Bulk-inserts one release per version of the chunk, maps the new ids back by `(publication_id, sequence)`
     * and links the versions in a single UPDATE. A constant number of statements per chunk, whatever its size.
     *
     * @param  array<int, int>  $sequences  the running sequence per publication_id, carried across chunks
     */
    private function backfillChunk(Collection $versions, array &$sequences): void
    {
        $unseen = $versions->map(fn ($version): int => (int) $version->publication_id)->unique()
            ->reject(fn (int $publicationId): bool => isset($sequences[$publicationId]));

        if ($unseen->isNotEmpty()) {
            $existing = DB::table('publication_releases')
                ->whereIn('publication_id', $unseen->all())
                ->groupBy('publication_id')
                ->select('publication_id')
                ->selectRaw('MAX('.DB::getQueryGrammar()->wrap('sequence').') as max_sequence')
                ->pluck('max_sequence', 'publication_id');

            foreach ($unseen as $publicationId) {
                $sequences[$publicationId] = (int) ($existing[$publicationId] ?? 0);
            }
        }

        $now = now();
        $releases = [];
        $firstSequence = [];
        $releaseKeyOf = [];

        foreach ($versions as $version) {
            $publicationId = (int) $version->publication_id;
            $sequence = ++$sequences[$publicationId];

            $firstSequence[$publicationId] ??= $sequence;
            $releaseKeyOf[(int) $version->id] = $publicationId.':'.$sequence;

            $releases[] = [
                'publication_id'  => $publicationId,
                'sequence'        => $sequence,
                'published_at'    => $version->published_at,
                'published_by_id' => $version->published_by_id,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('publication_releases')->insert($releases);

        $releaseIds = DB::table('publication_releases')
            ->where(function ($query) use ($firstSequence): void {
                foreach ($firstSequence as $publicationId => $sequence) {
                    $query->orWhere(fn ($match) => $match
                        ->where('publication_id', $publicationId)
                        ->where('sequence', '>=', $sequence));
                }
            })
            ->get(['id', 'publication_id', 'sequence'])
            ->mapWithKeys(fn ($release): array => [(int) $release->publication_id.':'.(int) $release->sequence => (int) $release->id]);

        $cases = collect($releaseKeyOf)
            ->map(fn (string $key, int $versionId): string => 'WHEN '.$versionId.' THEN '.$releaseIds[$key])
            ->implode(' ');

        DB::table('publication_versions')
            ->whereIn('id', array_keys($releaseKeyOf))
            ->update(['release_id' => DB::raw('CASE '.DB::getQueryGrammar()->wrap('id').' '.$cases.' END')]);
    }
};
