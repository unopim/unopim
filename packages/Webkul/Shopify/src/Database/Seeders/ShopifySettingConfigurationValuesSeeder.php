<?php

namespace Webkul\Shopify\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopifySettingConfigurationValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingIds = DB::table('shopify_setting_configuration_values')
            ->whereIn('id', [1, 2, 3])
            ->pluck('id')
            ->toArray();

        $records = [
            [
                'id'         => 1,
                'mapping'    => null,
                'extras'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'id'         => 2,
                'mapping'    => null,
                'extras'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'id'         => 3,
                'mapping'    => null,
                'extras'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $newRecords = array_filter($records, function ($record) use ($existingIds) {
            return ! in_array($record['id'], $existingIds);
        });

        if (! empty($newRecords)) {
            DB::table('shopify_setting_configuration_values')->insert($newRecords);
        }
    }
}
