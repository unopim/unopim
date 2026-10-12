<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;

/**
 * A product's media has to live under `product/<id>/<attribute code>/`: that is
 * where the product form uploads to, where a product copy relocates to, and the
 * only prefix `FileOrImageValidValue` accepts without an explicit allowance.
 * Seeding a shared path made every media field fail validation on save.
 */
function relocateDemoMedia(string $path, int $productId, string $code): ?string
{
    $seeder = new class extends DemoProductSeeder
    {
        public function relocatePublic(string $path, int $productId, string $code): ?string
        {
            return $this->relocate(Storage::disk('public'), $path, $productId, $code);
        }
    };

    return $seeder->relocatePublic($path, $productId, $code);
}

describe('demo media paths', function () {
    beforeEach(function (): void {
        Storage::fake('public');
    });

    it('moves a seeded file under the owning product', function () {
        Storage::disk('public')->put('demo/spec-sheets/outdoor.pdf', '%PDF-1.4');

        $target = relocateDemoMedia('demo/spec-sheets/outdoor.pdf', 4305, 'spec_sheet');

        expect($target)->toBe('product/4305/spec_sheet/outdoor.pdf')
            ->and(Storage::disk('public')->exists($target))->toBeTrue();
    });

    it('keeps a path that is already under the product', function () {
        Storage::disk('public')->put('product/12/image/audio-01.webp', 'webp');

        expect(relocateDemoMedia('product/12/image/audio-01.webp', 12, 'image'))
            ->toBe('product/12/image/audio-01.webp');
    });

    it('reports nothing to store when the source file is missing', function () {
        expect(relocateDemoMedia('demo/catalog/absent.webp', 7, 'image'))->toBeNull();
    });

    it('gives each product its own copy of a shared source file', function () {
        Storage::disk('public')->put('demo/catalog/audio-01.webp', 'webp');

        $first = relocateDemoMedia('demo/catalog/audio-01.webp', 100, 'image');
        $second = relocateDemoMedia('demo/catalog/audio-01.webp', 200, 'image');

        expect($first)->toBe('product/100/image/audio-01.webp')
            ->and($second)->toBe('product/200/image/audio-01.webp')
            ->and(Storage::disk('public')->exists($first))->toBeTrue()
            ->and(Storage::disk('public')->exists($second))->toBeTrue();
    });
});
