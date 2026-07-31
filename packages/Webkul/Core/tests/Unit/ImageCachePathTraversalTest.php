<?php

use Webkul\Core\ImageCache\Controller;

/**
 * The imagecache route is public and its filename pattern permits "." and "/",
 * so getImagePath() is the only thing standing between an anonymous request and
 * every file the PHP process can read. These lock that containment in place.
 */
function imageCacheRoot(): string
{
    static $root = null;

    if ($root === null) {
        $root = sys_get_temp_dir().'/unopim-imagecache-'.bin2hex(random_bytes(6));

        mkdir($root.'/media', 0775, true);

        file_put_contents($root.'/media/catalog-item.png', 'image-bytes');
        file_put_contents($root.'/app-secret.txt', 'APP_KEY=base64:leaked');
    }

    return $root;
}

function resolveImagePath(string $filename): string
{
    $method = new ReflectionMethod(Controller::class, 'getImagePath');

    return $method->invoke(new Controller, $filename);
}

beforeEach(function () {
    config(['imagecache.paths' => [imageCacheRoot().'/media']]);
});

afterAll(function () {
    $root = imageCacheRoot();

    foreach (['media/catalog-item.png', 'media/escape.txt', 'app-secret.txt'] as $file) {
        @unlink($root.'/'.$file);
    }

    @rmdir($root.'/media');
    @rmdir($root);
});

it('resolves a file that genuinely lives inside a configured root', function () {
    expect(resolveImagePath('catalog-item.png'))
        ->toBe(realpath(imageCacheRoot().'/media/catalog-item.png'));
});

it('refuses to resolve a filename that climbs out of the media roots', function (string $filename) {
    $resolved = resolveImagePath($filename);

    expect($resolved)->not->toContain('app-secret')
        ->and(file_exists($resolved))->toBeFalse();
})->with([
    '../app-secret.txt',
    '../../etc/passwd',
    'nested/../../app-secret.txt',
    '/../app-secret.txt',
    '..//app-secret.txt',
]);

it('refuses a symlink inside a root that points outside it', function () {
    $link = imageCacheRoot().'/media/escape.txt';

    @unlink($link);

    if (! @symlink(imageCacheRoot().'/app-secret.txt', $link)) {
        $this->markTestSkipped('symlinks are unavailable on this filesystem');
    }

    $resolved = resolveImagePath('escape.txt');

    expect($resolved)->not->toContain('app-secret')
        ->and(file_exists($resolved))->toBeFalse();
});

it('does not treat a dotted filename as traversal', function () {
    expect(resolveImagePath('catalog-item.png'))->toContain('catalog-item.png');
});
