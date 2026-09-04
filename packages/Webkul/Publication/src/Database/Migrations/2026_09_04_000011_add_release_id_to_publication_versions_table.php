<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publication_versions', function (Blueprint $table): void {
            $table->unsignedBigInteger('release_id')->nullable()->after('checksum');
            $table->foreign('release_id')->references('id')->on('publication_releases')->restrictOnDelete();
            $table->index('release_id', 'pubver_release_idx');
        });

        // Backfill: every existing version becomes its own release, numbered per publication in
        // publish order, so history reads the same way as anything minted from now on.
        $sequences = [];

        $versions = DB::table('publication_versions')
            ->select('id', 'publication_id', 'published_at', 'published_by_id')
            ->whereNull('release_id')
            ->orderBy('publication_id')
            ->orderBy('published_at')
            ->orderBy('id')
            ->cursor();

        foreach ($versions as $version) {
            $sequence = ($sequences[$version->publication_id] ?? 0) + 1;
            $sequences[$version->publication_id] = $sequence;

            $releaseId = DB::table('publication_releases')->insertGetId([
                'publication_id'  => $version->publication_id,
                'sequence'        => $sequence,
                'published_at'    => $version->published_at,
                'published_by_id' => $version->published_by_id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('publication_versions')->where('id', $version->id)->update(['release_id' => $releaseId]);
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
};
