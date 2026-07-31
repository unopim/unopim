<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates a ready-to-run import and export profile for every entity type the
 * data-transfer pipeline supports.
 *
 * Import profiles point at the sample file the package already ships for that
 * entity, copied onto the private disk the importer reads from, so an
 * evaluator can press Run instead of first authoring a CSV.
 */
class DemoJobSeeder extends Seeder
{
    protected const SAMPLE_SOURCE = 'data-transfer/samples';

    protected const IMPORT_TARGET = 'demo/imports';

    public function run(): void
    {
        $now = Date::now();

        $importers = array_keys(config('importers', []));
        $exporters = array_keys(config('exporters', []));

        DB::transaction(function () use ($importers, $exporters, $now): void {
            foreach ($importers as $entityType) {
                $this->seedImport((string) $entityType, $now);
            }

            foreach ($exporters as $entityType) {
                $this->seedExport((string) $entityType, $now);
            }
        });
    }

    protected function seedImport(string $entityType, $now): void
    {
        DB::table('job_instances')->updateOrInsert(
            ['code' => 'demo_import_'.str_replace('-', '_', $entityType)],
            [
                'entity_type'           => $entityType,
                'type'                  => 'import',
                'action'                => 'append',
                'validation_strategy'   => 'skip-erros',
                'allowed_errors'        => 10,
                'field_separator'       => ',',
                'file_path'             => $this->sampleFor($entityType),
                'images_directory_path' => null,
                'filters'               => json_encode(['file_format' => 'Csv'], JSON_THROW_ON_ERROR),
                'updated_at'            => $now,
                'created_at'            => $now,
            ],
        );
    }

    protected function seedExport(string $entityType, $now): void
    {
        DB::table('job_instances')->updateOrInsert(
            ['code' => 'demo_export_'.str_replace('-', '_', $entityType)],
            [
                'entity_type'         => $entityType,
                'type'                => 'export',
                'action'              => 'fetch',
                'validation_strategy' => 'skip',
                'allowed_errors'      => 0,
                'field_separator'     => ',',
                'file_path'           => null,
                'filters'             => json_encode($this->exportFilters($entityType), JSON_THROW_ON_ERROR),
                'updated_at'          => $now,
                'created_at'          => $now,
            ],
        );
    }

    /**
     * Copy the entity's shipped sample onto the private disk and return the
     * path, or null when the package ships no sample for it — an import
     * profile without a file is still valid, the operator uploads one.
     */
    protected function sampleFor(string $entityType): ?string
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');

        $source = self::SAMPLE_SOURCE.'/'.$entityType.'.csv';

        if (! $public->exists($source)) {
            return null;
        }

        $target = self::IMPORT_TARGET.'/'.$entityType.'.csv';

        $private->put($target, $public->get($source));

        return $target;
    }

    /**
     * Filters the export form would have submitted. Only the file format is
     * universal; the product exporter also takes a media flag.
     *
     * @return array<string, mixed>
     */
    protected function exportFilters(string $entityType): array
    {
        $filters = ['file_format' => 'Csv'];

        if (Str::startsWith($entityType, 'products')) {
            $filters['with_media'] = '0';
        }

        return $filters;
    }
}
