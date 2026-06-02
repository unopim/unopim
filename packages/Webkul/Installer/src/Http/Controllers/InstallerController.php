<?php

namespace Webkul\Installer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
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
     * again on a live instance. The `storage/installed` marker is written
     * only by the final SMTP step, so this never blocks a genuine install.
     *
     * @return void
     */
    protected function abortIfInstalled()
    {
        abort_if(file_exists(storage_path('installed')), 403);
    }

    /**
     * Write the completion marker that seals the installer.
     *
     * Once this file exists, `CanInstall` redirects every `/install` request
     * (including XHR) and {@see abortIfInstalled()} blocks the api endpoints.
     * It is written at the genuine end of the UI flow — after the admin is
     * created, and after demo data when the operator opts into it.
     *
     * @return void
     */
    protected function markInstalled()
    {
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

        return view('installer::installer.index', compact('requirements', 'phpVersion'));
    }

    /**
     * ENV File Setup
     */
    public function envFileSetup(Request $request): JsonResponse
    {
        $this->abortIfInstalled();

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

        try {
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
            ], 500);
        }

        // Admin is the final mandatory step. Seal the installer now unless the
        // operator opted into demo data, in which case `seedSampleData()`
        // (the genuine last call) writes the marker once seeding finishes.
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

        $result = $installer->seed();

        // Demo data is the final, optional step: the admin already exists, so
        // the instance is installed whether or not seeding succeeded. Seal the
        // installer either way (the UI advances to "completed" on failure too).
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
     * SMTP connection setup for Mail
     */
    public function smtpConfigSetup()
    {
        $this->abortIfInstalled();

        $this->environmentManager->setEnvConfiguration(request()->input());

        $filePath = storage_path('installed');

        File::put($filePath, 'Your UnoPim App is Successfully Installed');

        Event::dispatch('unopim.installed');

        return $filePath;
    }
}
