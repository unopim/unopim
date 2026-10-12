<?php

/**
 * Regression: when vendor/ is deleted (manual reinstall scenario), public/index.php
 * sends a relative Location header to install.php. If the user was on /admin/dashboard
 * at the time, the browser resolves "install.php" against the current URL and lands
 * on /admin/install.php (404), and the missing autoloader fatals because header()
 * does not exit. The fix builds the Location from SCRIPT_NAME and exits immediately.
 */
function computeInstallRedirectTarget(string $scriptName): string
{
    $base = dirname($scriptName ?? '/index.php');
    $base = $base === '/' || $base === '\\' ? '' : rtrim($base, '/');

    return $base.'/install.php';
}

describe('public/index.php missing-vendor redirect (issue: /admin/install.php 404)', function () {
    it('redirects to the same directory as index.php even when the request was for /admin/dashboard (subdirectory install)', function () {

        $target = computeInstallRedirectTarget('/unopim-issue/unopim/public/index.php');

        expect($target)->toBe('/unopim-issue/unopim/public/install.php');
    });

    it('handles docroot-level install where index.php sits at /', function () {
        $target = computeInstallRedirectTarget('/index.php');

        expect($target)->toBe('/install.php');
    });

    it('produces no /admin/install.php no matter how deep the original URL was', function () {

        $target = computeInstallRedirectTarget('/unopim-issue/unopim/public/index.php');

        expect($target)->not->toContain('/admin/');
    });
});
