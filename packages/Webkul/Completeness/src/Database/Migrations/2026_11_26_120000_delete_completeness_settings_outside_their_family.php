<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Settings saved for an attribute the family does not hold can never be satisfied, yet the scorer
 * reads every setting of the family, so each of them pulled the family's scores down. The
 * controllers now refuse such settings; this removes the ones saved before that.
 */
return new class extends Migration
{
    private const int CHUNK = 500;

    public function up(): void
    {
        $assigned = DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_family_group_mappings.id',
                '=',
                'attribute_group_mappings.attribute_family_group_id'
            )
            ->whereColumn('attribute_family_group_mappings.attribute_family_id', 'completeness_settings.family_id')
            ->whereColumn('attribute_group_mappings.attribute_id', 'completeness_settings.attribute_id');

        DB::table('completeness_settings')
            ->whereNotExists($assigned)
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($rows): void {
                DB::table('completeness_settings')->whereIn('id', $rows->pluck('id'))->delete();
            });
    }

    public function down(): void {}
};
