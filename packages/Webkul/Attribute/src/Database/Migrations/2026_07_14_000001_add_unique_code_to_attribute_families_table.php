<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->deduplicateCodes();

        Schema::table('attribute_families', function (Blueprint $table) {
            $table->unique('code', 'attribute_families_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_families', function (Blueprint $table) {
            $table->dropUnique('attribute_families_code_unique');
        });
    }

    /**
     * Existing installs may hold duplicate codes; suffix them so the index can be created.
     */
    protected function deduplicateCodes(): void
    {
        $duplicateCodes = DB::table('attribute_families')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        foreach ($duplicateCodes as $code) {
            $ids = DB::table('attribute_families')
                ->where('code', $code)
                ->orderBy('id')
                ->pluck('id')
                ->slice(1);

            foreach ($ids as $id) {
                DB::table('attribute_families')
                    ->where('id', $id)
                    ->update(['code' => $code.'_'.$id]);
            }
        }
    }
};
