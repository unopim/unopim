<?php

/**
 * Bootstrap installer for hosts without shell access.
 *
 * This file runs before the framework exists, so it cannot use Laravel helpers,
 * translations, or the autoloader — plain PHP and English output only.
 *
 * It shells out and rewrites .env, so it refuses to do anything once vendor/
 * is present. public/index.php only redirects here while the autoloader is
 * missing; on a deployed site a request to /install.php must be inert.
 */
$projectRoot = realpath(__DIR__.'/..') ?: dirname(__DIR__);

$alreadyInstalled = is_file($projectRoot.'/vendor/autoload.php');

$installationSuccessful = false;

/**
 * Write a line into the streamed output pane, escaped for HTML.
 *
 * @param  bool|null  $checked  append a tick / cross marker when not null
 */
function unopim_install_write(string $text, string $class = '', ?bool $checked = null): void
{
    $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $marker = $checked === null
        ? ''
        : ' <span class="'.($checked ? 'checked' : 'unchecked').'"></span>';

    echo $class === ''
        ? '<p>'.$escaped.$marker.'</p>'
        : '<p class="'.$class.'">'.$escaped.$marker.'</p>';

    unopim_install_flush();
}

/**
 * Push already-escaped process output straight to the browser.
 */
function unopim_install_raw(string $text): void
{
    echo htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    unopim_install_flush();
}

function unopim_install_flush(): void
{
    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
}

/**
 * Run a command, streaming stdout and stderr as they arrive.
 *
 * Both pipes are polled together: draining stdout to EOF first deadlocks as
 * soon as a chatty command (composer) fills the stderr buffer.
 *
 * @return int the exit code, or -1 when the process could not be started
 */
function unopim_install_run(string $command, string $workingDirectory): int
{
    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $pipes = [];

    $process = @proc_open($command, $descriptors, $pipes, $workingDirectory);

    if (! is_resource($process)) {
        return -1;
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $open = [1 => $pipes[1], 2 => $pipes[2]];

    while ($open !== []) {
        $read = array_values($open);
        $write = [];
        $except = [];

        if (@stream_select($read, $write, $except, 1) === false) {
            break;
        }

        foreach ($read as $stream) {
            $chunk = fread($stream, 8192);

            if ($chunk !== false && $chunk !== '') {
                unopim_install_raw($chunk);
            }

            if (! feof($stream)) {
                continue;
            }

            foreach ($open as $key => $candidate) {
                if ($candidate === $stream) {
                    unset($open[$key]);
                }
            }
        }
    }

    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }

    return proc_close($process);
}

/**
 * Resolve a runnable PHP CLI binary for spawned commands.
 *
 * `PHP_BINARY` is empty on some web SAPIs and points at php-fpm on others;
 * spawning either yields "Permission denied" and exit 127. Symfony's
 * PhpExecutableFinder is unavailable before composer install, so this probes
 * PHP_BINDIR and PATH by hand.
 */
function unopim_install_php_binary(): string
{
    if (PHP_BINARY !== '' && is_executable(PHP_BINARY) && ! str_contains(basename(PHP_BINARY), 'fpm')) {
        return PHP_BINARY;
    }

    $names = [
        'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
        'php'.PHP_MAJOR_VERSION,
        'php',
    ];

    $directories = array_merge(
        [PHP_BINDIR],
        explode(PATH_SEPARATOR, (string) getenv('PATH')),
        ['/usr/local/bin', '/usr/bin', '/opt/homebrew/bin']
    );

    foreach ($directories as $directory) {
        if ($directory === '') {
            continue;
        }

        foreach ($names as $name) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;

            if (is_executable($candidate)) {
                return $candidate;
            }
        }
    }

    return 'php';
}

/**
 * Whether the .env file already carries a non-empty APP_KEY.
 *
 * The installer never writes the .env file itself; `artisan key:generate` is
 * the single permitted exception, and only when no key exists yet — an
 * operator-authored key must never be rotated.
 */
function unopim_install_has_app_key(string $envPath): bool
{
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (preg_match('/^\s*APP_KEY\s*=\s*(.+)$/', $line, $matches)) {
            return trim(trim($matches[1]), "\"'") !== '';
        }
    }

    return false;
}

/**
 * Resolve the minimum PHP version from composer.json so the two cannot drift.
 */
function unopim_install_required_php(string $projectRoot): string
{
    $manifest = json_decode((string) @file_get_contents($projectRoot.'/composer.json'), true);

    $constraint = is_array($manifest) ? ($manifest['require']['php'] ?? '') : '';

    return preg_match('/(\d+\.\d+(?:\.\d+)?)/', (string) $constraint, $matches)
        ? $matches[1]
        : '8.4.1';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Checking for Dependency</title>
    <style>
        body {
            font-family: Inter;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #FFFFFF;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            gap: 16px;
        }

        .header {
            background-color: #fff;
            color: gray;
            text-align: left;
            font-size: 20px;
            font-weight: 700;
            line-height: 24.2px;
            padding: 16px;
            border-bottom: 1px solid #D9D9D9;
        }

        .output {
            padding: 20px;
            background-color: #000;
            color: #0f0;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-y: auto;
            max-height: 400px;
            white-space: pre-wrap;
        }

        .output p {
            margin: 0px;
        }

        .output-container {
            padding: 16px;
        }

        .footer {
            margin-top: 15px;
            display: flex !important;
            justify-content: end;
            align-items: center;
            gap:10px;
        }

        .footer a {
            color: #FFFFFF;
            border: 1px solid #6D28D8;
            text-decoration: none;
            font-size: 14px;
            background: #7C3AEC;
            padding: 6px 12px 6px 12px;
            gap: 4px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            line-height: 24px;
            text-underline-position: from-font;
            text-decoration-skip-ink: none;
        }

        .error{
            color: #e24d4d
        }

        .checked::before {
            content: "✔";
            color: #0f0;
            font-size: 20px;
        }

        .unchecked::before {
            content: "✖";
            color: red;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Checking for Dependency</div>
        <div class="output-container">
            <div class="output"><pre>
                <?php
                ob_start();

if ($alreadyInstalled) {
    unopim_install_write('This application is already installed.', 'error');
    unopim_install_write('The bootstrap installer is disabled once vendor/autoload.php exists, because it rewrites .env and regenerates the application key.', 'error');
    unopim_install_write('Remove vendor/ and retry only if you intend to reinstall from scratch.');
} else {
    $requiredPhpVersion = unopim_install_required_php($projectRoot);

    $phpVersion = PHP_VERSION;

    $ready = true;

    if (version_compare($phpVersion, $requiredPhpVersion, '<')) {
        unopim_install_write("PHP Version: $phpVersion", 'error', false);
        unopim_install_write("ERROR: PHP version must be >= $requiredPhpVersion.", 'error');

        $ready = false;
    } else {
        unopim_install_write("PHP Version: $phpVersion", '', true);
    }

    if ($ready) {
        $extensions = [
            'ctype',
            'curl',
            'dom',
            'fileinfo',
            'filter',
            'gd',
            'hash',
            'intl',
            'json',
            'mbstring',
            'openssl',
            'pcre',
            'simplexml',
        ];

        foreach ($extensions as $extension) {
            if (extension_loaded($extension)) {
                unopim_install_write("$extension extension is enabled.", '', true);
            } else {
                unopim_install_write("$extension extension is not enabled.", 'error', false);

                $ready = false;
            }
        }

        if (extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql')) {
            unopim_install_write('pdo_mysql or pdo_pgsql extension is enabled.', '', true);
        } else {
            unopim_install_write('Neither pdo_mysql nor pdo_pgsql is enabled.', 'error', false);

            $ready = false;
        }

        if (! $ready) {
            unopim_install_write('ERROR: Required extensions are not enabled. Please enable them and try again.', 'error');
        }
    }

    $composerPhar = $projectRoot.'/bin/composer/composer.phar';

    if ($ready && ! is_file($composerPhar)) {
        unopim_install_write('ERROR: bin/composer/composer.phar is missing.', 'error');

        $ready = false;
    }

    if ($ready) {
        $composerHome = $projectRoot.'/storage/composer';

        if (! is_dir($composerHome)) {
            @mkdir($composerHome, 0775, true);
        }

        putenv('COMPOSER_HOME='.$composerHome);

        $phpBinary = unopim_install_php_binary();

        $commands = [
            escapeshellarg($phpBinary).' '.escapeshellarg($composerPhar)
                .' install --no-ansi --no-interaction --working-dir='.escapeshellarg($projectRoot),
        ];

        $envPath = $projectRoot.'/.env';

        if (is_file($envPath)) {
            unopim_install_write('.env found.', '', true);
        } elseif (trim((string) getenv('APP_KEY')) !== '') {
            unopim_install_write('No .env file — using the environment provided by the server.', '', true);
        } else {
            unopim_install_write('ERROR: No .env file and no APP_KEY in the server environment. This installer never writes environment configuration — upload a .env (copy .env.example and fill in your values) or configure the variables in your hosting panel, then try again.', 'error');

            $ready = false;
        }

        if ($ready && is_file($envPath) && ! unopim_install_has_app_key($envPath) && trim((string) getenv('APP_KEY')) === '') {
            $commands[] = escapeshellarg($phpBinary).' '
                .escapeshellarg($projectRoot.'/artisan').' key:generate --force';
        }

        foreach ($commands as $command) {
            unopim_install_write("Executing: $command");

            $exitCode = unopim_install_run($command, $projectRoot);

            if ($exitCode === -1) {
                unopim_install_write("Failed to execute the command: $command", 'error');

                $ready = false;

                break;
            }

            if ($exitCode !== 0) {
                unopim_install_write("Command failed with exit code: $exitCode.", 'error');

                $ready = false;

                break;
            }

            unopim_install_write("Command finished successfully with exit code: $exitCode.");
        }
    }

    if ($ready) {
        unopim_install_write('All commands executed.');
    }

    $installationSuccessful = $ready;
}

ob_end_flush();
?>
            </pre></div>

            <div class="footer">
                <?php
if ($alreadyInstalled) {
    echo '<a href="./">Go to application</a>';
} elseif ($installationSuccessful) {
    echo '<a href="install">Continue</a>';
} else {
    echo '<p class="error"> Installation Failed. Please check output above and try again. </p>';
    echo '<a href="install.php">Try Again</a>';
}
?>
            </div>
        </div>
    </div>
</body>
</html>
