<?php

use Webkul\Core\Helpers\MediaContent;

function writePdfFixture(string $head, int $padding, string $tail): string
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');

    $handle = fopen($path, 'wb');

    fwrite($handle, $head);

    for ($written = 0; $written < $padding; $written += 65536) {
        fwrite($handle, str_repeat(' ', min(65536, $padding - $written)));
    }

    fwrite($handle, $tail);
    fclose($handle);

    return $path;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/pdf*') as $file) {
        @unlink($file);
    }
});

it('detects active content in the head of the file', function () {
    $path = writePdfFixture("%PDF-1.7\n/OpenAction << /S /JavaScript >>\n", 0, "%%EOF\n");

    expect(MediaContent::pdfHasActiveContent($path))->toBeTrue();
});

it('detects active content pushed past the head window by padding', function () {
    $path = writePdfFixture("%PDF-1.7\n", 9 * 1024 * 1024, "/OpenAction << /S /JavaScript >>\n%%EOF\n");

    expect(MediaContent::pdfHasActiveContent($path))->toBeTrue();
});

it('accepts a padded pdf that carries no active content', function () {
    $path = writePdfFixture("%PDF-1.7\n", 9 * 1024 * 1024, "/Type /Catalog\n%%EOF\n");

    expect(MediaContent::pdfHasActiveContent($path))->toBeFalse();
});

it('reports no active content for a missing or unreadable path', function () {
    expect(MediaContent::pdfHasActiveContent(null))->toBeFalse()
        ->and(MediaContent::pdfHasActiveContent('/no/such/file.pdf'))->toBeFalse();
});
