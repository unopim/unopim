<?php

use Webkul\Core\Helpers\MediaContent;

function writeOoxmlFixture(bool $withMacroProject): string
{
    $path = tempnam(sys_get_temp_dir(), 'ooxml');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>');

    if ($withMacroProject) {
        $zip->addFromString('word/vbaProject.bin', 'x');
    }

    $zip->close();

    return $path;
}

function writeBinaryFixture(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'ooxml');

    file_put_contents($path, $contents);

    return $path;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/ooxml*') as $file) {
        @unlink($file);
    }
});

it('reports the pdf reason for a pdf carrying an open action', function () {
    $path = writeBinaryFixture("%PDF-1.7\n/OpenAction << /S /JavaScript >>\n%%EOF\n");

    expect(MediaContent::activeContentReason('pdf', $path))->toBe('embedded_javascript_or_action');
});

it('reports a vba macro for an ooxml package shipping a vba project part', function () {
    expect(MediaContent::activeContentReason('docx', writeOoxmlFixture(true)))->toBe('embedded_vba_macro')
        ->and(MediaContent::activeContentReason('PPTX', writeOoxmlFixture(true)))->toBe('embedded_vba_macro');
});

it('accepts an ooxml package without a vba project part', function () {
    expect(MediaContent::activeContentReason('docx', writeOoxmlFixture(false)))->toBeNull();
});

it('accepts an ooxml extension whose contents are not a zip', function () {
    expect(MediaContent::activeContentReason('docx', writeBinaryFixture('not a zip')))->toBeNull();
});

it('reports a vba macro for a legacy binary office document', function () {
    $path = writeBinaryFixture("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\0", 512)."V\0B\0A\0");

    expect(MediaContent::activeContentReason('doc', $path))->toBe('embedded_vba_macro');
});

it('accepts a legacy binary office document without a vba stream', function () {
    $path = writeBinaryFixture("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\0", 512)."W\0o\0r\0d\0");

    expect(MediaContent::activeContentReason('doc', $path))->toBeNull();
});

it('reports an auto-executing object for rtf carrying objupdate or objautlink', function () {
    expect(MediaContent::activeContentReason('rtf', writeBinaryFixture('{\rtf1{\object\objupdate}}')))->toBe('auto_executing_ole_object')
        ->and(MediaContent::activeContentReason('rtf', writeBinaryFixture('{\rtf1{\object\OBJAUTLINK}}')))->toBe('auto_executing_ole_object')
        ->and(MediaContent::activeContentReason('rtf', writeBinaryFixture('{\rtf1{\object\objemb}}')))->toBeNull();
});

it('does not scan formats it has no markers for', function () {
    $path = writeBinaryFixture('/OpenAction /JavaScript V\0B\0A\0 \objupdate');

    expect(MediaContent::activeContentReason('txt', $path))->toBeNull()
        ->and(MediaContent::activeContentReason(null, $path))->toBeNull()
        ->and(MediaContent::activeContentReason('pdf', null))->toBeNull()
        ->and(MediaContent::activeContentReason('pdf', '/no/such/file.pdf'))->toBeNull();
});
