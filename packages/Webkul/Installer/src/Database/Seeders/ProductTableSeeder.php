<?php

namespace Webkul\Installer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Throwable;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

class ProductTableSeeder extends Seeder
{
    public function run(array $parameters = []): void
    {
        $products = $this->buildProducts();

        if ($products === null) {
            return;
        }

        if (empty($products)) {
            $this->command?->warn('No valid products to insert — skipping database insert.');

            return;
        }

        try {
            DB::transaction(function () use ($products): void {
                DB::table('products')->delete();
                DB::table('products')->insert($products);
            });

            $this->command?->info('Products imported successfully.');
        } catch (Throwable $e) {
            $this->command?->error('Failed to insert products: '.$e->getMessage());

            return;
        }

        DatabaseSequenceHelper::fixSequences(['products']);
    }

    private function buildProducts(): ?array
    {
        $jsonPath = __DIR__.'/../Data/products.json';

        if (! File::exists($jsonPath)) {
            $this->command?->error('products.json file not found.');

            return null;
        }

        try {
            $data = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->command?->error('Failed to parse products.json: '.$e->getMessage());

            return null;
        }

        if (! isset($data['products'])) {
            $this->command?->error('Invalid JSON format: missing "products" key.');

            return null;
        }

        $now = Carbon::now();
        $products = [];

        foreach ($data['products'] as $product) {
            try {
                $values = $product['values'];

                if (isset($values['common']['image'])) {
                    $storedImagePath = $this->storeProductImage($values['common']['image']);

                    if ($storedImagePath !== null) {
                        $values['common']['image'] = $storedImagePath;
                    } else {
                        unset($values['common']['image']);
                    }
                }

                $products[] = [
                    'sku'                 => $product['sku'],
                    'type'                => $product['type'] ?? 'simple',
                    'status'              => 1,
                    'attribute_family_id' => $product['attribute_family_id'] ?? 1,
                    'values'              => json_encode($values, JSON_THROW_ON_ERROR),
                    'additional'          => null,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            } catch (JsonException $e) {
                $this->command?->error('Failed to encode values for product: '.($product['sku'] ?? 'unknown').' - '.$e->getMessage());
            } catch (Throwable $e) {
                $this->command?->error('Failed to process product: '.($product['sku'] ?? 'unknown').' - '.$e->getMessage());
            }
        }

        return $products;
    }

    /**
     * Store the product image from package seeder data to the public storage disk.
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

        $destinationPath = 'products/'.basename($sourcePath);

        Storage::disk('public')->put(
            $destinationPath,
            File::get($sourcePath)
        );

        return $destinationPath;
    }
}
