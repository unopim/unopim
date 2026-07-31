<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Facades\DB;

class SystemInformation
{
    /**
     * Package names highlighted at the top of the packages list.
     *
     * @var string[]
     */
    protected array $keyPackages = [
        'laravel/framework',
        'laravel/octane',
        'laravel/passport',
        'laravel/sanctum',
        'konekt/concord',
        'elasticsearch/elasticsearch',
        'astrotomic/laravel-translatable',
        'maatwebsite/excel',
        'prettus/l5-repository',
        'kalnoy/nestedset',
    ];

    /**
     * PHP extensions UnoPim depends on.
     *
     * @var string[]
     */
    protected array $requiredExtensions = [
        'calendar', 'curl', 'intl', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'gd', 'zip', 'fileinfo',
    ];

    /**
     * Build the full system information payload grouped by section.
     *
     * @return array<string, array<string, string>>
     */
    public function all(): array
    {
        return [
            'application' => $this->application(),
            'server'      => $this->server(),
            'database'    => $this->database(),
            'services'    => $this->services(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function application(): array
    {
        return [
            'unopim_version'  => core()->version(),
            'laravel_version' => app()->version(),
            'php_version'     => PHP_VERSION,
            'environment'     => (string) app()->environment(),
            'debug_mode'      => $this->bool((bool) config('app.debug')),
            'timezone'        => (string) config('app.timezone'),
            'locale'          => (string) config('app.locale'),
            'url'             => (string) config('app.url'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function server(): array
    {
        return [
            'operating_system'    => PHP_OS.' '.php_uname('r'),
            'server_software'     => (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'cli'),
            'php_sapi'            => PHP_SAPI,
            'memory_limit'        => (string) ini_get('memory_limit'),
            'max_execution_time'  => (string) ini_get('max_execution_time'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size'       => (string) ini_get('post_max_size'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function database(): array
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        $version = 'unknown';

        try {
            $result = DB::selectOne('select version() as version');
            $version = $result->version ?? 'unknown';
        } catch (\Throwable) {
            $version = 'unknown';
        }

        return [
            'connection' => $connection,
            'driver'     => $driver,
            'version'    => $version,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function services(): array
    {
        return [
            'cache_driver'          => (string) config('cache.default'),
            'queue_connection'      => (string) config('queue.default'),
            'session_driver'        => (string) config('session.driver'),
            'mail_mailer'           => (string) config('mail.default'),
            'elasticsearch_enabled' => $this->bool((bool) config('elasticsearch.enabled')),
        ];
    }

    /**
     * The PHP extensions UnoPim requires, mapped to whether they are loaded.
     *
     * @return array<string, bool>
     */
    public function extensions(): array
    {
        $extensions = [];

        foreach ($this->requiredExtensions as $extension) {
            $extensions[$extension] = extension_loaded($extension);
        }

        return $extensions;
    }

    /**
     * Installed composer packages and their versions, key packages first.
     *
     * @return array<string, string>
     */
    public function packages(): array
    {
        $installedFile = base_path('vendor/composer/installed.php');

        if (! is_file($installedFile)) {
            return [];
        }

        $installed = require $installedFile;
        $versions = $installed['versions'] ?? [];

        $packages = [];

        foreach ($versions as $name => $metadata) {
            if (! isset($metadata['pretty_version'])) {
                continue;
            }

            $packages[$name] = (string) $metadata['pretty_version'];
        }

        ksort($packages);

        $key = [];

        foreach ($this->keyPackages as $name) {
            if (isset($packages[$name])) {
                $key[$name] = $packages[$name];
                unset($packages[$name]);
            }
        }

        return $key + $packages;
    }

    /**
     * Normalize a boolean to a translatable enabled/disabled label key value.
     */
    protected function bool(bool $value): string
    {
        return $value
            ? trans('admin::app.help.system-info.enabled')
            : trans('admin::app.help.system-info.disabled');
    }
}
