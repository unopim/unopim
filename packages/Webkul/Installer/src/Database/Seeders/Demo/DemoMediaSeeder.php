<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Publishes the demo imagery to the public disk and renders one spec-sheet
 * PDF per attribute family for the `spec_sheet` and `assembly_manual` file
 * attributes.
 *
 * The PDFs are generated rather than committed so they always describe the
 * families the seeders actually created.
 */
class DemoMediaSeeder extends Seeder
{
    public const CATALOG_PATH = 'demo/catalog';

    public const SHEET_PATH = 'demo/spec-sheets';

    public function run(): void
    {
        $this->publishImages();

        $this->renderSpecSheets();
    }

    protected function publishImages(): void
    {
        $source = realpath(__DIR__.'/../../../Resources/assets/images/demo/catalog');

        if ($source === false || ! File::isDirectory($source)) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (File::files($source) as $file) {
            $disk->put(
                self::CATALOG_PATH.'/'.$file->getFilename(),
                File::get($file->getRealPath())
            );
        }
    }

    /**
     * Render a one-page datasheet per family. A missing PDF engine must not
     * fail the seed — the file attribute simply stays empty.
     */
    protected function renderSpecSheets(): void
    {
        $disk = Storage::disk('public');

        $families = DB::table('attribute_families')
            ->join('attribute_family_translations', function ($join): void {
                $join->on('attribute_family_translations.attribute_family_id', '=', 'attribute_families.id')
                    ->where('attribute_family_translations.locale', '=', 'en_US');
            })
            ->pluck('attribute_family_translations.name', 'attribute_families.code');

        foreach ($families as $code => $name) {
            try {
                $pdf = Pdf::loadView('installer::demo.spec-sheet', [
                    'family' => $name ?: $code,
                    'code'   => $code,
                ]);

                $disk->put(self::SHEET_PATH.'/'.$code.'.pdf', $pdf->output());
            } catch (Throwable) {
                return;
            }
        }
    }
}
