<?php

namespace Webkul\Installer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Webkul\Installer\Console\Commands\Installer;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\DemoDataInstaller;
use Webkul\Installer\Helpers\EnvironmentManager;
use Webkul\Installer\Helpers\ServerRequirements;

class InstallerController extends Controller
{
    /**
     * Const Variable For Min PHP Version
     *
     * @var string
     */
    const MIN_PHP_VERSION = '8.2.0';

    /**
     * Const Variable for Static Customer Id
     *
     * @var int
     */
    const USER_ID = 1;

    /**
     * Cloud hosting promo URL shown in the installer top bar.
     *
     * @var string
     */
    const CLOUD_HOSTING_URL = 'https://unopim.com/cloud-hosting/';

    /**
     * Optional open-source add-on packages surfaced in the web installer.
     *
     * Mirrors {@see Installer::$optionalPackages}
     * 1:1 so the labels and install commands stay in sync with the CLI. The web
     * installer installs the selected packages server-side during the streaming
     * install (see {@see installPackageStreamed()}); the client only sends the
     * whitelisted keys, never the composer/artisan arguments.
     *
     * Display labels live in the `installer::app.installer.index.add-ons.packages.*`
     * lang files and are rendered client-side; only machine values are kept here.
     *
     * @var array<string, array{composer: string, install: string}>
     */
    protected array $optionalPackages = [
        'dam' => [
            'composer' => 'unopim/dam',
            'install'  => 'dam-package:install',
        ],
        'shopify' => [
            'composer' => 'unopim/shopify-connector',
            'install'  => 'shopify-package:install',
        ],
        'bagisto' => [
            'composer' => 'unopim/bagisto-connector',
            'install'  => 'bagisto-package:install',
        ],
    ];

    /**
     * Create a new controller instance
     *
     * @return void
     */
    public function __construct(
        protected ServerRequirements $serverRequirements,
        protected EnvironmentManager $environmentManager,
        protected DatabaseManager $databaseManager
    ) {}

    /**
     * Abort with 403 once the application is fully installed.
     *
     * Defence in depth for the unauthenticated installer api endpoints: even
     * if the `CanInstall` middleware were bypassed (e.g. a crafted header or
     * a future routing change), the state-changing setup steps must never run
     * again on a live instance. The `storage/installed` marker is written only
     * at the end of the install flow (after admin creation, and after demo data
     * when opted in), so this never blocks a genuine install.
     */
    protected function abortIfInstalled(): void
    {
        abort_if(
            file_exists(storage_path('installed'))
                || $this->databaseManager->isMarkedInstalled(),
            403
        );
    }

    /**
     * Abort with 403 when the database is already populated. Guards the
     * destructive pre-admin steps (migration/seed/env) against being replayed
     * on an installed instance whose storage marker was lost.
     */
    protected function abortIfDatabasePopulated(): void
    {
        abort_if($this->databaseManager->isInstalled(), 403);
    }

    /**
     * Apply the database credentials from the freshly written .env to the live
     * runtime config.
     *
     * The .env is written by an earlier request (env-file-setup), but the
     * process handling migration/seeding may have already booted with the old
     * config (persistent `php artisan serve` workers, php-fpm, Octane, etc.),
     * so it would otherwise migrate the wrong database. Re-reading the .env and
     * purging the connection makes the install work on any server, mirroring
     * the CLI installer's loadEnvConfigAtRuntime().
     */
    protected function reloadDatabaseConfigFromEnv(): void
    {
        $env = $this->readEnvFile();

        if (empty($env)) {
            return;
        }

        $connection = $env['DB_CONNECTION'] ?? config('database.default');

        config([
            'database.default'                                    => $connection,
            "database.connections.{$connection}.host"             => $env['DB_HOST'] ?? '127.0.0.1',
            "database.connections.{$connection}.port"             => $env['DB_PORT'] ?? '3306',
            "database.connections.{$connection}.database"         => $env['DB_DATABASE'] ?? '',
            "database.connections.{$connection}.username"         => $env['DB_USERNAME'] ?? '',
            "database.connections.{$connection}.password"         => $env['DB_PASSWORD'] ?? '',
            "database.connections.{$connection}.prefix"           => $env['DB_PREFIX'] ?? '',
        ]);

        DB::purge($connection);
    }

    /**
     * Parse the .env file into a key => value map.
     *
     * @return array<string, string>
     */
    protected function readEnvFile(): array
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return [];
        }

        $vars = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $vars[trim($key)] = trim(trim($value), "\"'");
        }

        return $vars;
    }

    /**
     * Write the completion marker that seals the installer.
     *
     * Once this file exists, `CanInstall` redirects every `/install` request
     * (including XHR) and {@see abortIfInstalled()} blocks the api endpoints.
     * It is written at the genuine end of the UI flow — after the admin is
     * created, and after demo data when the operator opts into it. Guarded so
     * the marker is written, and `unopim.installed` dispatched, exactly once
     * even if two end-of-flow requests race.
     */
    protected function markInstalled(): void
    {
        $this->databaseManager->markInstalled();

        if (file_exists(storage_path('installed'))) {
            return;
        }

        File::put(storage_path('installed'), 'Your UnoPim App is Successfully Installed');

        Event::dispatch('unopim.installed');
    }

    /**
     * Installer View Root Page
     *
     * @return View
     */
    public function index()
    {
        $phpVersion = $this->serverRequirements->checkPHPversion(self::MIN_PHP_VERSION);

        $requirements = $this->serverRequirements->validate();

        if (request()->has('locale')) {
            return redirect()->route('installer.index');
        }

        $optionalPackages = $this->optionalPackages;

        $cloudHostingUrl = self::CLOUD_HOSTING_URL;

        return view('installer::installer.index', compact('requirements', 'phpVersion', 'optionalPackages', 'cloudHostingUrl'));
    }

    /**
     * ENV File Setup
     */
    public function envFileSetup(Request $request): JsonResponse
    {
        $this->abortIfInstalled();

        // Refuse to rewrite a live .env when the database is already populated,
        // even if the install marker/flag were lost (prevents the .env-reset takeover).
        $this->abortIfDatabasePopulated();

        $request = $request->all();

        if (isset($request['db_prefix'])) {
            $request['db_prefix'] = trim((string) $request['db_prefix']);
        }

        $request = array_map(function ($input) {
            return strip_tags((string) $input);
        }, $request);

        // Match the CLI installer's prefix validation 1:1 so both install
        // paths surface the same migration-blocking errors up-front.
        $validator = Validator::make($request, [
            'db_prefix' => ['nullable', 'string', 'max:4', 'regex:/^[A-Za-z0-9_]*$/'],
        ], [
            'db_prefix.max'   => 'The database prefix should not exceed 4 characters.',
            'db_prefix.regex' => 'The database prefix can only contain letters, numbers, and underscores.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => $validator->errors()->first('db_prefix') ?: 'Failed to parse dotenv file due to some invalid values',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $message = $this->environmentManager->generateEnv($request);

        return new JsonResponse(['data' => $message]);
    }

    /**
     * Run Migration
     */
    public function runMigration()
    {
        $this->abortIfInstalled();
        $this->abortIfDatabasePopulated();

        $this->reloadDatabaseConfigFromEnv();

        try {
            $this->databaseManager->createDatabaseIfNotExists();

            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        $migration = $this->databaseManager->migration();

        return $migration;
    }

    /**
     * Run Seeder
     *
     * @return void|string
     */
    public function runSeeder()
    {
        $this->abortIfInstalled();
        $this->abortIfDatabasePopulated();

        $this->reloadDatabaseConfigFromEnv();

        $selectedParameters = request()->selectedParameters;
        $allParameters = request()->allParameters;

        $appLocale = $allParameters['app_locale'] ?? null;
        $appCurrency = $allParameters['app_currency'] ?? null;

        $allowedLocales = array_unique(array_merge(
            [($appLocale ?? 'en_US')],
            $selectedParameters['allowed_locales']
        ));

        $allowedCurrencies = array_unique(array_merge(
            [($appCurrency ?? 'USD')],
            $selectedParameters['allowed_currencies']
        ));

        $parameter = [
            'parameter' => [
                'default_locales'    => $appLocale,
                'default_currency'   => $appCurrency,
                'allowed_locales'    => $allowedLocales,
                'allowed_currencies' => $allowedCurrencies,
            ],
        ];

        $response = $this->environmentManager->setEnvConfiguration(request()->allParameters);

        if ($response) {
            $seeder = $this->databaseManager->seeder($parameter);

            return $seeder;
        }
    }

    /**
     * Admin Configuration Setup.
     *
     * @return void
     */
    public function adminConfigSetup()
    {
        $this->abortIfInstalled();

        $this->reloadDatabaseConfigFromEnv();

        $password = password_hash(request()->input('password'), PASSWORD_BCRYPT, ['cost' => 10]);
        $uiLocaleId = DB::table('locales')->where('code', request()->input('locale'))->where('status', 1)->first()?->id ?? 58;

        try {
            DB::table('admins')->updateOrInsert(
                [
                    'id' => self::USER_ID,
                ], [
                    'name'         => request()->input('admin'),
                    'email'        => request()->input('email'),
                    'timezone'     => request()->input('timezone'),
                    'ui_locale_id' => $uiLocaleId,
                    'password'     => $password,
                    'role_id'      => 1,
                    'status'       => 1,
                ]
            );
        } catch (\Throwable $th) {
            report($th);

            return response()->json([
                'success' => false,
                'error'   => $th->getMessage(),
                'errors'  => ['admin' => [$th->getMessage()]],
            ], 500);
        }

        if (! request()->boolean('seed_sample_data')) {
            $this->markInstalled();
        }
    }

    /**
     * Run the demo extras, demo categories, and sample product seeders.
     *
     * Invoked from the UI installer when the operator opts into sample
     * data on the create-admin step. Returns 200 with `success: true`
     * on success, 500 with the seeder error message otherwise.
     */
    public function seedSampleData(DemoDataInstaller $installer): JsonResponse
    {
        $this->abortIfInstalled();

        $this->reloadDatabaseConfigFromEnv();

        $result = $installer->seed();

        $this->markInstalled();

        if (! ($result['success'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $result['error'] ?? 'Failed to seed sample data.',
            ], 500);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Persist the install configuration server-side ahead of the streaming run.
     *
     * Writes the app/locale/currency/Elasticsearch settings to the .env via
     * {@see EnvironmentManager::setEnvConfiguration()} and stashes the admin
     * credentials, sample-data flag, allowed locales/currencies and the
     * (whitelisted) optional-package keys in the session. The actual install is
     * driven by {@see processInstall()} over a GET EventSource, which cannot
     * carry a body — so the admin password never travels in a query string; it
     * is read back from the session inside the SSE stream.
     */
    public function prepareInstall(Request $request): JsonResponse
    {
        $this->abortIfInstalled();

        $this->reloadDatabaseConfigFromEnv();

        $payload = $request->all();

        $this->environmentManager->setEnvConfiguration($payload);

        $admin = $request->input('admin', []);

        $allowedLocales = (array) $request->input('allowed_locales', []);
        $allowedCurrencies = (array) $request->input('allowed_currencies', []);

        $requestedPackages = (array) $request->input('packages', []);

        // Whitelist package keys against the server-side map — never trust the
        // client with composer/artisan arguments. Unknown keys are dropped.
        $packages = array_values(array_filter(
            array_map('strval', $requestedPackages),
            fn ($key) => isset($this->optionalPackages[$key])
        ));

        // Persist to a temp state file rather than the session: the SSE stream is
        // a separate GET request and the session is unreliable across the install
        // (APP_KEY is regenerated during env-file-setup, the sessions table may
        // not exist yet, etc.). The file is consumed and deleted by processInstall.
        File::put(
            $this->installerStatePath(),
            json_encode([
                'admin' => [
                    'admin'    => strip_tags((string) ($admin['admin'] ?? '')),
                    'email'    => strip_tags((string) ($admin['email'] ?? '')),
                    'password' => (string) ($admin['password'] ?? ''),
                    'timezone' => strip_tags((string) ($admin['timezone'] ?? 'UTC')),
                    'locale'   => strip_tags((string) ($admin['locale'] ?? 'en_US')),
                ],
                'sample'             => $request->boolean('sample'),
                'packages'           => $packages,
                'app_locale'         => strip_tags((string) $request->input('app_locale', 'en_US')),
                'app_currency'       => strip_tags((string) $request->input('app_currency', 'USD')),
                'allowed_locales'    => array_map(fn ($v) => strip_tags((string) $v), $allowedLocales),
                'allowed_currencies' => array_map(fn ($v) => strip_tags((string) $v), $allowedCurrencies),
            ])
        );

        // The state file holds the admin password until processInstall consumes it,
        // so restrict it to the owner only (never group/world readable).
        @chmod($this->installerStatePath(), 0600);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Path of the transient installer state file written by prepareInstall and
     * consumed (then deleted) by processInstall.
     */
    protected function installerStatePath(): string
    {
        return storage_path('app/installer-state.json');
    }

    /**
     * Run the entire installation server-side and stream it to the browser.
     *
     * Returns a Server-Sent Events stream (consumed by an EventSource in the
     * wizard's terminal panel). Each step emits its log lines live; a failing
     * add-on never aborts the run. The flow mirrors the CLI installer:
     * database → migrate → seed → admin → optional sample data → optional
     * add-on packages → seal. Admin credentials and selections are read from the
     * session populated by {@see prepareInstall()}, so nothing sensitive rides
     * in the GET query string.
     */
    public function processInstall(): StreamedResponse
    {
        $this->abortIfInstalled();

        // processInstall runs `migrate:fresh` (drops every table). Guard it the
        // same way runMigration() does: if the DB is already populated (an
        // installed instance whose storage marker was lost), refuse — otherwise a
        // reachable install endpoint would wipe the live database.
        $this->abortIfDatabasePopulated();

        $statePath = $this->installerStatePath();
        $state = file_exists($statePath) ? (json_decode(File::get($statePath), true) ?: []) : [];

        $admin = (array) ($state['admin'] ?? []);
        $sample = (bool) ($state['sample'] ?? false);
        $packages = (array) ($state['packages'] ?? []);
        $appLocale = $state['app_locale'] ?? 'en_US';
        $appCurrency = $state['app_currency'] ?? 'USD';
        $allowedLocales = (array) ($state['allowed_locales'] ?? []);
        $allowedCurrencies = (array) ($state['allowed_currencies'] ?? []);

        $response = new StreamedResponse(function () use (
            $statePath,
            $admin,
            $sample,
            $packages,
            $appLocale,
            $appCurrency,
            $allowedLocales,
            $allowedCurrencies
        ) {
            $emit = function (string $text): void {
                echo 'data: '.json_encode(['line' => $text]).PHP_EOL.PHP_EOL;

                @ob_flush();
                @flush();
            };

            $emitLines = function (string $output) use ($emit): void {
                foreach (preg_split('/\r\n|\r|\n/', rtrim($output)) as $line) {
                    if (trim($line) !== '') {
                        $emit($line);
                    }
                }
            };

            // Best effort for shared hosting: lift the execution-time limit and
            // keep running even if the browser disconnects mid-install.
            @set_time_limit(0);
            @ignore_user_abort(true);

            // Disable output buffering/compression so the terminal streams live
            // where the host permits; the install still completes if it buffers.
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            @ob_implicit_flush(true);

            try {
                // a. Database
                $this->reloadDatabaseConfigFromEnv();
                $emit(trans('installer::app.installer.index.terminal.preparing-database'));

                try {
                    $this->databaseManager->createDatabaseIfNotExists();
                } catch (\Throwable $e) {
                    // Shared hosting commonly forbids CREATE DATABASE (the DB is
                    // created via cPanel). Note it and continue; the connection
                    // check below surfaces a clear error if it truly is missing.
                    $emit('  ('.$e->getMessage().')');
                }

                DB::connection()->getPdo();
                $emit(trans('installer::app.installer.index.terminal.database-ready'));

                // b. Migrate
                $emit(trans('installer::app.installer.index.terminal.migrating'));
                Artisan::call('migrate:fresh', ['--force' => true]);
                $emitLines(Artisan::output());
                $emit(trans('installer::app.installer.index.terminal.migrated'));

                // c. Seed base data
                $emit(trans('installer::app.installer.index.terminal.seeding'));

                $locales = array_values(array_unique(array_merge([$appLocale], $allowedLocales)));
                $currencies = array_values(array_unique(array_merge([$appCurrency], $allowedCurrencies)));

                $this->databaseManager->seeder([
                    'parameter' => [
                        'default_locales'    => $appLocale,
                        'default_locale'     => $appLocale,
                        'default_currency'   => $appCurrency,
                        'allowed_locales'    => $locales,
                        'allowed_currencies' => $currencies,
                    ],
                ]);
                $emit(trans('installer::app.installer.index.terminal.seeded'));

                // d. Administrator
                $emit(trans('installer::app.installer.index.terminal.creating-admin'));
                $this->createAdminFromSession($admin);
                $emit(trans('installer::app.installer.index.terminal.admin-created'));

                // e. Sample data (optional)
                if ($sample) {
                    $emit(trans('installer::app.installer.index.terminal.installing-sample'));

                    $result = app(DemoDataInstaller::class)->seed();

                    if (! ($result['success'] ?? false)) {
                        $emit(trans('installer::app.installer.index.terminal.sample-failed', [
                            'message' => $result['error'] ?? '',
                        ]));
                    } else {
                        $emit(trans('installer::app.installer.index.terminal.sample-done'));
                    }
                }

                // f. Optional add-on packages
                foreach ($packages as $key) {
                    if (! isset($this->optionalPackages[$key])) {
                        continue;
                    }

                    $package = $this->optionalPackages[$key];

                    $this->installPackageStreamed($package, $emit, $emitLines);
                }

                // g. Seal the installation.
                $this->markInstalled();

                echo 'event: done'.PHP_EOL;
                echo 'data: '.json_encode(['redirect' => '/admin']).PHP_EOL.PHP_EOL;

                @ob_flush();
                @flush();
            } catch (\Throwable $e) {
                report($e);

                echo 'event: error'.PHP_EOL;
                echo 'data: '.json_encode(['message' => $e->getMessage()]).PHP_EOL.PHP_EOL;

                @ob_flush();
                @flush();
            } finally {
                // Always remove the transient state file — it holds the admin
                // password — whatever the outcome of the stream.
                @unlink($statePath);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    /**
     * Insert the administrator record from the session-stored credentials.
     *
     * Mirrors {@see adminConfigSetup()} but reads from the session (populated by
     * {@see prepareInstall()}) so the password is never carried in the SSE GET
     * request.
     *
     * @param  array<string, string>  $admin
     */
    protected function createAdminFromSession(array $admin): void
    {
        $password = password_hash((string) ($admin['password'] ?? ''), PASSWORD_BCRYPT, ['cost' => 10]);

        $uiLocaleId = DB::table('locales')
            ->where('code', $admin['locale'] ?? 'en_US')
            ->where('status', 1)
            ->first()?->id ?? 58;

        DB::table('admins')->updateOrInsert(
            ['id' => self::USER_ID],
            [
                'name'         => $admin['admin'] ?? 'Admin',
                'email'        => $admin['email'] ?? 'admin@example.com',
                'timezone'     => $admin['timezone'] ?? 'UTC',
                'ui_locale_id' => $uiLocaleId,
                'password'     => $password,
                'role_id'      => 1,
                'status'       => 1,
            ]
        );
    }

    /**
     * Install one optional package, streaming Composer and artisan output live.
     *
     * Mirrors the CLI installer (`composer require` then the package's own
     * artisan install command, both spawned as fresh processes). A failure for
     * one package is reported with the manual fallback commands and never aborts
     * the overall install.
     *
     * @param  array{composer: string, label: string, install: string}  $package
     */
    protected function installPackageStreamed(array $package, callable $emit, callable $emitLines): void
    {
        $emit(trans('installer::app.installer.index.terminal.installing-package', [
            'label'    => $package['label'],
            'composer' => $package['composer'],
        ]));

        // Shared/FTP-only hosting has no shell access (proc_open disabled, no
        // composer binary), so do not attempt to run anything — show the manual
        // commands to run from a machine that does have shell access instead.
        if (! $this->canRunProcesses()) {
            $emit(trans('installer::app.installer.index.terminal.package-manual', [
                'label' => $package['label'],
            ]));

            $emit('  composer require '.$package['composer'].' && php artisan '.$package['install']);

            return;
        }

        $stream = function ($type, $buffer) use ($emitLines): void {
            $emitLines($buffer);
        };

        // Composer needs a writable HOME/COMPOSER_HOME and the binary on PATH —
        // a web process often has neither, so set them explicitly.
        $composerHome = storage_path('app/installer-composer');

        if (! is_dir($composerHome)) {
            @mkdir($composerHome, 0775, true);
        }

        $env = [
            'COMPOSER_HOME'           => $composerHome,
            'HOME'                    => $composerHome,
            'COMPOSER_NO_INTERACTION' => '1',
            'PATH'                    => getenv('PATH').PATH_SEPARATOR.'/usr/local/bin'.PATH_SEPARATOR.'/usr/bin',
        ];

        $composerBin = $this->resolveComposerBinary();

        try {
            $composer = new Process([...$composerBin, 'require', $package['composer']], base_path(), $env, null, null);
            $composer->run($stream);

            if (! $composer->isSuccessful()) {
                throw new \RuntimeException($composer->getErrorOutput() ?: 'composer require failed');
            }

            $artisan = new Process(
                [PHP_BINARY, base_path('artisan'), $package['install'], '--no-interaction'],
                base_path(),
                $this->resolvedDatabaseEnv() + $env,
                null,
                null
            );
            $artisan->run($stream);

            if (! $artisan->isSuccessful()) {
                throw new \RuntimeException($artisan->getErrorOutput() ?: 'package install command failed');
            }

            $emit(trans('installer::app.installer.index.terminal.package-installed', [
                'label' => $package['label'],
            ]));
        } catch (\Throwable $e) {
            $emit(trans('installer::app.installer.index.terminal.package-failed', [
                'label'   => $package['label'],
                'message' => $e->getMessage(),
            ]));

            $emit('  composer require '.$package['composer'].' && php artisan '.$package['install']);
        }
    }

    /**
     * Whether the host can spawn external processes (composer / artisan).
     *
     * FTP-only shared hosting disables proc_open (and ships no composer binary),
     * so the add-on auto-install must degrade to printing the manual commands.
     */
    protected function canRunProcesses(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));

        return ! in_array('proc_open', $disabled, true);
    }

    /**
     * Locations probed for a composer executable, in priority order.
     *
     * @return array<int, string>
     */
    protected function composerProbePaths(): array
    {
        return [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            base_path('composer.phar'),
            base_path('bin/composer/composer.phar'),
        ];
    }

    /**
     * Resolve the composer executable as a process-argument prefix.
     *
     * A web process PATH may not include composer, so probe common locations
     * and the project-local / bundled composer.phar files before falling
     * back to bare "composer".
     *
     * @return array<int, string>
     */
    protected function resolveComposerBinary(): array
    {
        foreach ($this->composerProbePaths() as $path) {
            if (is_file($path)) {
                return str_ends_with($path, '.phar') ? [PHP_BINARY, $path] : [$path];
            }
        }

        return ['composer'];
    }

    /**
     * Database environment derived from the resolved connection, passed to the
     * spawned package-install process so it talks to the same database.
     *
     * @return array<string, string>
     */
    protected function resolvedDatabaseEnv(): array
    {
        $connection = config('database.default');

        return [
            'DB_CONNECTION' => (string) $connection,
            'DB_HOST'       => (string) config("database.connections.{$connection}.host"),
            'DB_PORT'       => (string) config("database.connections.{$connection}.port"),
            'DB_DATABASE'   => (string) config("database.connections.{$connection}.database"),
            'DB_USERNAME'   => (string) config("database.connections.{$connection}.username"),
            'DB_PASSWORD'   => (string) config("database.connections.{$connection}.password"),
            'DB_PREFIX'     => (string) config("database.connections.{$connection}.prefix"),
        ];
    }

    /**
     * SMTP connection setup for Mail
     */
    public function smtpConfigSetup()
    {
        $this->abortIfInstalled();

        $this->abortIfDatabasePopulated();

        $this->environmentManager->setEnvConfiguration(request()->input());

        $filePath = storage_path('installed');

        File::put($filePath, 'Your UnoPim App is Successfully Installed');

        Event::dispatch('unopim.installed');

        return $filePath;
    }
}
