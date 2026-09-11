<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_gtins', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('publication_id');
            $table->foreign('publication_id')->references('id')->on('publications')->restrictOnDelete();

            $table->string('gtin', 14);

            $table->dateTime('recorded_at');

            $table->timestamps();

            // Explicit names: auto names include the prefix and overrun MySQL's 64-char identifier limit on prefixed installs.
            $table->unique(['publication_id', 'gtin'], 'pubgtin_pub_gtin_uq');
            $table->index('gtin', 'pubgtin_gtin_idx');
        });

        // Backfill: every GTIN a publication currently carries is the first entry of its history.
        $rows = DB::table('publications')
            ->whereNotNull('gtin')
            ->where('gtin', '!=', '')
            ->get(['id', 'gtin', 'last_published_at', 'created_at']);

        foreach ($rows as $row) {
            DB::table('publication_gtins')->insert([
                'publication_id' => $row->id,
                'gtin'           => $row->gtin,
                'recorded_at'    => $row->last_published_at ?? $row->created_at ?? now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_gtins');
    }
};
