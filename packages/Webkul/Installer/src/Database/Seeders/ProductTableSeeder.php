<?php

namespace Webkul\Installer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Throwable;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

/**
 * Seeds the demo catalog from the JSON payload in
 * `Installer/src/Database/Data/products.json`.
 *
 * Each entry in the `products` array may describe either a simple or
 * a configurable product. Configurables support an optional
 * `variants` array of children (inserted with the parent's id as
 * `parent_id`) and a `super_attributes` list of attribute codes that
 * are written to `product_super_attributes`.
 */
class ProductTableSeeder extends Seeder
{
    public function run(): void
    {
        $data = $this->loadProductData();

        if ($data === null) {
            return;
        }

        if (empty($data['parents'])) {
            $this->command?->warn('No valid products to insert — skipping database insert.');

            return;
        }

        try {
            DB::transaction(function () use ($data): void {
                $this->clearExistingProducts();

                DB::table('products')->insert($data['parents']);

                $parentMap = DB::table('products')
                    ->select('id', 'sku')
                    ->whereIn('sku', array_column($data['parents'], 'sku'))
                    ->pluck('id', 'sku');

                $variantRows = [];
                foreach ($data['variants'] as $variant) {
                    $parentId = $parentMap[$variant['parent_sku']] ?? null;

                    if (! $parentId) {
                        continue;
                    }

                    $variantRows[] = [
                        'sku'                 => $variant['sku'],
                        'type'                => 'simple',
                        'status'              => 1,
                        'parent_id'           => $parentId,
                        'attribute_family_id' => $variant['attribute_family_id'],
                        'values'              => $variant['values'],
                        'additional'          => null,
                        'created_at'          => $variant['created_at'],
                        'updated_at'          => $variant['updated_at'],
                    ];
                }

                if (! empty($variantRows)) {
                    DB::table('products')->insert($variantRows);
                }

                $this->insertSuperAttributes($data['super_attributes'], $parentMap);
            });

            $this->copySeederMedia();

            $this->command?->info('Products imported successfully.');
        } catch (Throwable $e) {
            $this->command?->error('Failed to insert products: '.$e->getMessage());

            return;
        }

        DatabaseSequenceHelper::fixSequences(['products']);
    }

    /**
     * Remove any existing seeded products so the command is idempotent.
     */
    protected function clearExistingProducts(): void
    {
        DB::table('product_super_attributes')->delete();
        DB::table('products')->whereNotNull('parent_id')->delete();
        DB::table('products')->delete();
    }

    /**
     * Parse products.json into insert-ready rows.
     *
     * @return array{parents: array<int, array<string, mixed>>, variants: array<int, array<string, mixed>>, super_attributes: array<string, array<int, string>>}|null
     */
    private function loadProductData(): ?array
    {
        $jsonPath = __DIR__.'/../Data/products.json';

        if (! File::exists($jsonPath)) {
            $this->command?->error('products.json file not found.');

            return null;
        }

        try {
            $decoded = json_decode(
                File::get($jsonPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->command?->error('Failed to parse products.json: '.$e->getMessage());

            return null;
        }

        if (! isset($decoded['products']) || ! is_array($decoded['products'])) {
            $this->command?->error('Invalid JSON format: missing "products" key.');

            return null;
        }

        $now = Carbon::now();
        $parents = [];
        $variants = [];
        $superAttributes = [];

        foreach ($decoded['products'] as $product) {
            try {
                $sku = $product['sku'] ?? null;
                if (! $sku) {
                    continue;
                }

                $type = $product['type'] ?? 'simple';
                $familyId = (int) ($product['attribute_family_id'] ?? 1);
                $values = $this->prepareValues($product['values'] ?? []);

                $parents[] = [
                    'sku'                 => $sku,
                    'type'                => $type,
                    'status'              => 1,
                    'attribute_family_id' => $familyId,
                    'values'              => json_encode($values, JSON_THROW_ON_ERROR),
                    'additional'          => null,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];

                if ($type !== 'configurable') {
                    continue;
                }

                if (! empty($product['super_attributes']) && is_array($product['super_attributes'])) {
                    $superAttributes[$sku] = array_values($product['super_attributes']);
                }

                foreach ($product['variants'] ?? [] as $variant) {
                    $variantSku = $variant['sku'] ?? null;
                    if (! $variantSku) {
                        continue;
                    }

                    $variantValues = $this->prepareValues($variant['values'] ?? []);

                    $variants[] = [
                        'sku'                 => $variantSku,
                        'parent_sku'          => $sku,
                        'attribute_family_id' => $familyId,
                        'values'              => json_encode($variantValues, JSON_THROW_ON_ERROR),
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
            } catch (JsonException $e) {
                $this->command?->error(
                    'Failed to encode values for product: '
                    .($product['sku'] ?? 'unknown')
                    .' - '.$e->getMessage()
                );
            } catch (Throwable $e) {
                $this->command?->error(
                    'Failed to process product: '
                    .($product['sku'] ?? 'unknown')
                    .' - '.$e->getMessage()
                );
            }
        }

        return [
            'parents'          => $parents,
            'variants'         => $variants,
            'super_attributes' => $superAttributes,
        ];
    }

    /**
     * Normalise a product's values payload, copying seeder images to
     * the public disk and dropping unresolved image references.
     */
    protected function prepareValues(array $values): array
    {
        if (! isset($values['common']['image'])) {
            return $values;
        }

        $storedImagePath = $this->storeProductImage($values['common']['image']);

        if ($storedImagePath !== null) {
            $values['common']['image'] = $storedImagePath;
        } else {
            unset($values['common']['image']);
        }

        return $values;
    }

    /**
     * Attach super attributes to each configurable parent.
     *
     * @param  array<string, array<int, string>>  $superAttributes
     * @param  Collection<string, int>  $parentMap
     */
    protected function insertSuperAttributes(array $superAttributes, $parentMap): void
    {
        if (empty($superAttributes)) {
            return;
        }

        $defaultFamilyId = (int) (
            DB::table('attribute_families')
                ->where('code', 'default')
                ->value('id') ?? 1
        );

        $attributeCodes = array_unique(array_merge(...array_values($superAttributes)));

        $attributeIds = DB::table('attributes')
            ->join(
                'attribute_group_mappings',
                'attributes.id',
                '=',
                'attribute_group_mappings.attribute_id'
            )
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $defaultFamilyId)
            ->whereIn('attributes.code', $attributeCodes)
            ->where('attributes.type', 'select')
            ->where('attributes.value_per_locale', 0)
            ->where('attributes.value_per_channel', 0)
            ->pluck('attributes.id', 'attributes.code');

        $pivotRows = [];

        foreach ($superAttributes as $sku => $codes) {
            $parentId = $parentMap[$sku] ?? null;
            if (! $parentId) {
                continue;
            }

            foreach ($codes as $code) {
                if (! isset($attributeIds[$code])) {
                    $this->command?->warn(
                        "Attribute code \"$code\" not eligible in family \"default\". Pivot row skipped for $sku."
                    );

                    continue;
                }

                $pivotRows[] = [
                    'product_id'   => (int) $parentId,
                    'attribute_id' => (int) $attributeIds[$code],
                ];
            }
        }

        if (! empty($pivotRows)) {
            DB::table('product_super_attributes')->insertOrIgnore($pivotRows);
        }
    }

    /**
     * Store the product image referenced in the JSON payload under the
     * public disk, returning the stored relative path.
     */
    protected function storeProductImage(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        $baseDataPath = __DIR__.'/../../Resources/assets/images/seeders/';
        $sourcePath = $baseDataPath.ltrim($imagePath, '/');
        $defaultPath = $baseDataPath.'products/default.jpg';

        if (! File::exists($sourcePath)) {
            if (! File::exists($defaultPath)) {
                return null;
            }

            $sourcePath = $defaultPath;
        }

        $destinationPath = ltrim($imagePath, '/');

        Storage::disk('public')->put(
            $destinationPath,
            File::get($sourcePath)
        );

        return $destinationPath;
    }

    /**
     * Recursively copy every media file shipped under
     * `Resources/assets/images/seeders/` to the public disk. Ensures
     * references to gallery / channel-scoped images in the JSON
     * payload resolve on a fresh install even when they are not the
     * primary `common.image` ref.
     */
    protected function copySeederMedia(): void
    {
        $sourceRoot = realpath(__DIR__.'/../../Resources/assets/images/seeders');

        if ($sourceRoot === false || ! File::isDirectory($sourceRoot)) {
            return;
        }

        foreach (File::allFiles($sourceRoot) as $file) {
            $relative = ltrim(
                str_replace('\\', '/', substr($file->getRealPath(), strlen($sourceRoot) + 1)),
                '/'
            );

            if ($relative === '') {
                continue;
            }

            Storage::disk('public')->put($relative, File::get($file->getRealPath()));
        }
    }
}
