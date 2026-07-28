<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'related_products' => 'Related Products',
            'up_sells'         => 'Up Sells',
            'cross_sells'      => 'Cross Sells',
        ];

        $position = 1;

        foreach ($defaults as $code => $name) {
            DB::table('association_types')->updateOrInsert(
                ['code' => $code],
                ['status' => 1, 'position' => $position++, 'is_user_defined' => 0, 'updated_at' => now(), 'created_at' => now()]
            );

            $id = DB::table('association_types')->where('code', $code)->value('id');

            DB::table('association_type_translations')->updateOrInsert(
                ['association_type_id' => $id, 'locale' => 'en_US'],
                ['name' => $name]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('association_types')->whereIn('code', ['related_products', 'up_sells', 'cross_sells'])->delete();
    }
};
