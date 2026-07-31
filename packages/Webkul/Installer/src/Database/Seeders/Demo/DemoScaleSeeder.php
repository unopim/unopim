<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Pads the catalog to a target row count by cloning the authored products.
 *
 * Opt-in via `unopim:install:demo-data --scale=large`, for evaluating grid,
 * filter and export behaviour at a size the authored catalog does not reach.
 * Clones are marked in their SKU so they are never mistaken for the curated
 * products they are derived from.
 */
class DemoScaleSeeder extends Seeder
{
    public const LARGE_TARGET = 2000;

    public const CLONE_PREFIX = 'demo-scale-';

    public function run(int $target = self::LARGE_TARGET): void
    {
        $sources = DB::table('products')
            ->whereNull('parent_id')
            ->where('sku', 'not like', self::CLONE_PREFIX.'%')
            ->get(['sku', 'type', 'attribute_family_id', 'values']);

        if ($sources->isEmpty()) {
            return;
        }

        DB::table('products')->where('sku', 'like', self::CLONE_PREFIX.'%')->delete();

        $existing = (int) DB::table('products')->count();
        $missing = $target - $existing;

        if ($missing <= 0) {
            return;
        }

        $now = Date::now();
        $rows = [];
        $index = 0;

        while (count($rows) < $missing) {
            $source = $sources[$index % $sources->count()];
            $sequence = intdiv($index, $sources->count()) + 1;
            $index++;

            $sku = self::CLONE_PREFIX.$sequence.'-'.$source->sku;

            $rows[] = [
                'sku'                 => $sku,
                'type'                => 'simple',
                'status'              => 1,
                'attribute_family_id' => $source->attribute_family_id,
                'values'              => $this->rewriteIdentity($source->values, $sku, $sequence),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }
    }

    /**
     * Give the clone its own identity values; everything unique in the schema
     * (sku, url_key, ean) has to differ or the insert fails.
     */
    protected function rewriteIdentity(string $values, string $sku, int $sequence): string
    {
        $decoded = json_decode($values, true, 512, JSON_THROW_ON_ERROR);

        $decoded['common']['sku'] = $sku;
        $decoded['common']['url_key'] = $sku;

        if (isset($decoded['common']['ean'])) {
            $decoded['common']['ean'] = substr($decoded['common']['ean'], 0, 6).str_pad((string) ($sequence % 10000000), 7, '0', STR_PAD_LEFT);
        }

        if (isset($decoded['common']['product_number'])) {
            $decoded['common']['product_number'] .= '-'.$sequence;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
