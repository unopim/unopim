<?php

use Webkul\DataTransfer\Jobs\Export\File\LocalTemporaryFile;

/*
 * Guards against the export-file exposure: finished exports must not be written
 * under storage/app/public, which the storage:link symlink serves unauthenticated.
 * They belong on the private disk, reachable only through the ACL-gated download.
 */
it('writes export files to the private disk, not the public web root', function () {
    $file = new LocalTemporaryFile('exports/1/uno-pim/product.csv', 'exports/1/uno-pim');

    expect($file->getLocalPath())
        ->toContain('app'.DIRECTORY_SEPARATOR.'private')
        ->not->toContain('app'.DIRECTORY_SEPARATOR.'public');
});
