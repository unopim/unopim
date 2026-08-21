<?php

/**
 * MariaDB has no CI matrix, so a driver branch that forgets it fails silently
 * in production rather than here. Laravel reports `mariadb` as a driver of its
 * own, so a file that decides anything on `mysql` and never mentions it is
 * taking the wrong branch on every MariaDB install.
 *
 * Scoped per file rather than per branch: a file may legitimately single out
 * `mysql` once it is already handling the family (BackupManager excludes
 * MariaDB from MySQL's `--no-tablespaces`), and the failure this guards against
 * is forgetting the driver outright.
 */
function driverBranchOffenders(): array
{
    $root = base_path();

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root.'/packages', FilesystemIterator::SKIP_DOTS)
    );

    $offenders = [];

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/tests/')) {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());

        if (! str_contains($source, 'getDriverName')) {
            continue;
        }

        if (! preg_match("/(===|!==|=>|case)\s*'mysql'|'mysql'\s*(=>|,)/", $source)) {
            continue;
        }

        if (! str_contains($source, 'mariadb')) {
            $offenders[] = str_replace($root.'/', '', $file->getPathname());
        }
    }

    return $offenders;
}

it('names mariadb wherever it branches on the mysql driver', function () {
    expect(driverBranchOffenders())->toBeEmpty();
});
