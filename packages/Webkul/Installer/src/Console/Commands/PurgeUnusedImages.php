<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeUnusedImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:images:purge-unused {--dry-run : List unused images without deleting them} {--tenant= : Tenant ID to scope image purging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge unused images from the system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->option('tenant') && class_exists(\Webkul\Tenant\Providers\TenantServiceProvider::class)) {
            $this->error('Multi-tenant mode detected. You must specify --tenant or run for each tenant individually.');

            return 1;
        }

        if ($tenantOption = $this->option('tenant')) {
            $tenant = DB::table('tenants')->where('id', $tenantOption)->first();

            if (! $tenant || $tenant->status !== 'active') {
                $this->error('Tenant not found or not active.');

                return 1;
            }

            core()->setCurrentTenantId((int) $tenantOption);
            $this->info("Running in tenant context: {$tenant->name} (ID: {$tenant->id})");
        }

        $this->info('Starting purge of unused images...');

        $imageAttributes = DB::table('attributes')
            ->whereIn('type', ['image', 'gallery'])
            ->pluck('code')
            ->toArray();

        $allImages = $this->getAllImagesFromStorage();
        $countAllImages = count($allImages);

        if ($countAllImages === 0) {
            $this->warn('No images found in the storage.');

            return 0;
        }

        $this->info('Found '.$countAllImages.' images in storage.');

        $usedImages = $this->getUsedImagesFromDatabase($imageAttributes);
        $this->info('Found '.count($usedImages).' used images in the database.');

        $unusedImages = array_diff($allImages, $usedImages);
        $countUnusedImages = count($unusedImages);

        if ($countUnusedImages === 0) {
            $this->info('No unused images found.');

            return 0;
        }

        $this->info('Found '.count($unusedImages).' unused images.');

        if ($this->option('dry-run')) {
            $this->handleDryRun($unusedImages);

            return 0;
        }

        $this->deleteUnusedImages($unusedImages);

        $this->info('Unused image purge completed.');

        return 0;
    }

    private function getUsedImagesFromDatabase(array $imageAttributes): array
    {
        $query = DB::table('products');

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId)) {
            $query->where('products.tenant_id', $tenantId);
        }

        return $query
            ->pluck('values')
            ->map(fn ($value) => $this->extractImagesFromProduct(json_decode($value, true), $imageAttributes))
            ->flatten()
            ->filter()
            ->map(fn ($path) => ltrim($path, '/'))
            ->unique()
            ->toArray();
    }

    private function extractImagesFromProduct(?array $data, array $imageAttributes): array
    {
        if (! $data) {
            return [];
        }

        $sections = ['common', 'locale_specific', 'channel_specific', 'channel_locale_specific'];

        return collect($sections)
            ->flatMap(fn ($section) => $this->processSection($section, $data, $imageAttributes))
            ->toArray();
    }

    private function processSection(string $section, array $data, array $imageAttributes): array
    {
        if (! isset($data[$section]) || ! is_array($data[$section])) {
            return [];
        }

        $sectionData = $data[$section];

        return match ($section) {
            'locale_specific', 'channel_specific' => $this->extractLocaleOrChannelSpecificImages($sectionData, $imageAttributes),
            'channel_locale_specific' => $this->extractChannelLocaleSpecificImages($sectionData, $imageAttributes),
            'common'                  => $this->extractImagesFromAttributes($sectionData, $imageAttributes),
            default                   => [],
        };
    }

    private function extractImagesFromAttributes(array $attributes, array $imageAttributes): array
    {
        return collect($attributes)
            ->filter(fn ($value, $key) => in_array($key, $imageAttributes))
            ->flatMap(function ($value) {
                return is_array($value) ? $value : [$value];
            })
            ->toArray();
    }

    private function extractLocaleOrChannelSpecificImages(array $sectionData, array $imageAttributes): array
    {
        return collect($sectionData)
            ->flatMap(fn ($attributes) => $this->extractImagesFromAttributes($attributes, $imageAttributes))
            ->toArray();
    }

    private function extractChannelLocaleSpecificImages(array $sectionData, array $imageAttributes): array
    {
        return collect($sectionData)
            ->flatMap(fn ($channelData) => collect($channelData)
                ->flatMap(fn ($attributes) => $this->extractImagesFromAttributes($attributes, $imageAttributes))
                ->toArray())
            ->toArray();
    }

    private function deleteEmptyDirectories(string $directory, string $baseDirectory)
    {
        if (Storage::disk('public')->exists($directory)) {
            $files = Storage::disk('public')->files($directory);
            $directories = Storage::disk('public')->directories($directory);

            if (empty($files) && empty($directories) && $directory !== $baseDirectory) {
                Storage::disk('public')->deleteDirectory($directory);

                $parentDirectory = dirname($directory);
                if ($parentDirectory !== '.' && $parentDirectory !== '/' && $parentDirectory !== $baseDirectory) {
                    $this->deleteEmptyDirectories($parentDirectory, $baseDirectory);
                }
            }
        }
    }

    private function handleDryRun(array $unusedImages): void
    {
        $this->info('Dry run enabled. Listing unused images without removing them:');
        foreach ($unusedImages as $unusedImage) {
            $this->line($unusedImage);
        }
    }

    private function getAllImagesFromStorage(): array
    {
        return collect(Storage::disk('public')->allFiles('product'))
            ->map(fn ($path) => ltrim($path, '/'))
            ->toArray();
    }

    private function deleteUnusedImages(array $unusedImages): void
    {
        foreach ($unusedImages as $unusedImage) {
            if (Storage::disk('public')->exists($unusedImage)) {
                Storage::disk('public')->delete($unusedImage);
                $this->info("Deleted: $unusedImage");

                // Check and delete empty directories recursively
                $directory = dirname($unusedImage);
                $this->deleteEmptyDirectories($directory, 'product');
            } else {
                $this->warn("File not found: $unusedImage");
            }
        }
    }
}
