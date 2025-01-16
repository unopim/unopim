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
    protected $signature = 'unopim:images:purge-unused {--dry-run : List unused images without deleting them}';

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
        $this->info('Starting purge of unused images...');

        $imageAttributes = DB::table('attributes')
            ->whereIn('type', ['image', 'gallery'])
            ->pluck('code')
            ->toArray();

        $usedImages = DB::table('products')
            ->pluck('values')
            ->map(function ($value) use ($imageAttributes) {
                $data = json_decode($value, true);

                if (! $data) {
                    return [];
                }

                $sections = ['common', 'locale_specific', 'channel_specific', 'channel_locale_specific'];
                $images = collect($sections)
                    ->flatMap(function ($section) use ($data, $imageAttributes) {
                        if (! isset($data[$section]) || ! is_array($data[$section])) {
                            return [];
                        }

                        $sectionData = $data[$section];
                        // Handle locale/channel-specific sections
                        if (in_array($section, ['locale_specific', 'channel_specific'])) {
                            return collect($sectionData)
                                ->flatMap(function ($attributes) use ($imageAttributes) {
                                    return $this->extractImagesFromAttributes($attributes, $imageAttributes);
                                })
                                ->toArray();
                        }

                        // Handle channel-locale-specific sections
                        if ($section === 'channel_locale_specific') {
                            return collect($sectionData)
                                ->flatMap(function ($channelData) use ($imageAttributes) {
                                    return collect($channelData)
                                        ->flatMap(function ($attributes) use ($imageAttributes) {
                                            return $this->extractImagesFromAttributes($attributes, $imageAttributes);
                                        })
                                        ->toArray();
                                })
                                ->toArray();
                        }

                        // Handle common section
                        return $this->extractImagesFromAttributes($sectionData, $imageAttributes);
                    })
                    ->toArray();

                return $images;
            })
            ->flatten()
            ->filter()
            ->map(fn ($path) => ltrim($path, '/'))
            ->unique()
            ->toArray();

        $this->info('Found '.count($usedImages).' used images in the database.');

        $allImages = Storage::disk('public')->allFiles('product');
        $allImages = collect($allImages)->map(function ($path) {
            return ltrim($path, '/');
        })->toArray();

        $this->info('Found '.count($allImages).' images in storage.');

        $unusedImages = array_diff($allImages, $usedImages);
        $this->info('Found '.count($unusedImages).' unused images.');

        if ($this->option('dry-run')) {
            $this->info('Dry run enabled. Listing unused images:');
            foreach ($unusedImages as $unusedImage) {
                $this->line($unusedImage);
            }

            return 0;
        }

        foreach ($unusedImages as $unusedImage) {
            if (Storage::disk('public')->exists($unusedImage)) {
                Storage::disk('public')->delete($unusedImage);
                $this->info("Deleted: $unusedImage");

                $directory = dirname($unusedImage);
                $this->deleteEmptyDirectories($directory, 'product');
            } else {
                $this->warn("File not found: $unusedImage");
            }
        }

        $this->info('Unused image purge completed.');

        return 0;
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
}
