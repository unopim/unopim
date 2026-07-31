<?php

namespace Webkul\DataTransfer\Services;

use Illuminate\Support\Facades\Storage;

class SampleFiles
{
    /**
     * Prefix every configured sample path carries, so the remainder doubles as
     * the path inside the package's own `Resources/samples` directory.
     */
    public const PUBLIC_PREFIX = 'data-transfer/samples/';

    public const DEFAULT_KEY = 'default';

    /**
     * Every sample offered for a job type, keyed by download key.
     *
     * @return array<string, array{path: string, label: string, images_path: ?string}>
     */
    public function all(string $configFile, string $type): array
    {
        $config = config("$configFile.$type");

        if (! is_array($config)) {
            return [];
        }

        $samples = [];

        if (isset($config['sample_path'])) {
            $samples[self::DEFAULT_KEY] = [
                'path'        => $config['sample_path'],
                'label'       => $config['sample_label'] ?? 'data_transfer::app.samples.default',
                'images_path' => $config['sample_images_path'] ?? null,
            ];
        }

        foreach ($config['samples'] ?? [] as $key => $sample) {
            $samples[$key] = [
                'path'        => $sample['path'],
                'label'       => $sample['label'],
                'images_path' => $sample['images_path'] ?? null,
            ];
        }

        return $samples;
    }

    /**
     * Absolute path of a sample, or null when the type or key is unknown.
     *
     * A copy published to the `public` disk wins over the file shipped in the
     * package, so an installation can tailor its samples without a fork.
     */
    public function path(string $configFile, string $type, ?string $key = null, bool $images = false): ?string
    {
        $sample = $this->all($configFile, $type)[$key ?: self::DEFAULT_KEY] ?? null;

        $path = $images ? ($sample['images_path'] ?? null) : ($sample['path'] ?? null);

        if (! $path) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $packagePath = dirname(__DIR__).'/Resources/samples/'.str_replace(self::PUBLIC_PREFIX, '', $path);

        return is_file($packagePath) ? $packagePath : null;
    }
}
