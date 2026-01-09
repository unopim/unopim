<?php

namespace Webkul\Installer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Webkul\Installer\Helpers\DatabaseManager;
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
     * Installer View Root Page
     *
     * @return \Illuminate\Contracts\View\View
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
        $rules = [
            'db_prefix' => 'not_regex:/[^A-Za-z0-9_]/',
        ];

        $request = $request->all();

        $request = array_map(function ($input) {
            return strip_tags($input);
        }, $request);

        $validator = Validator::make($request, $rules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Failed to parse dotenv file due to some invalid values'], 422);
        }

        $message = $this->environmentManager->generateEnv($request);

        return new JsonResponse(['data' => $message]);
    }

    /**
     * Run Migration
     */
    public function runMigration()
    {
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
                'default_locales'     => $appLocale,
                'default_currency'    => $appCurrency,
                'allowed_locales'     => $allowedLocales,
                'allowed_currencies'  => $allowedCurrencies,
                'skip_admin_creation' => true,
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
        $password = password_hash(request()->input('password'), PASSWORD_BCRYPT, ['cost' => 10]);
        $uiLocaleId = DB::table('locales')->where('code', request()->input('locale'))->where('status', 1)->first()?->id ?? 58;

        try {
            DB::table('admins')->insert([
                'id'      => self::USER_ID,
                'name'    => request()->input('admin'),
                'email'   => request()->input('email'),
                'password'=> $password,
                'role_id' => 1,
                'status'  => 1,
            ]);
        } catch (\Throwable $th) {
            // dd($th);
            Log::error('Error in Admin installer config setup: '.$th->getMessage());
        }
    }

    /**
     * SMTP connection setup for Mail
     */
    public function smtpConfigSetup()
    {
        $this->environmentManager->setEnvConfiguration(request()->input());

        $filePath = storage_path('installed');

        File::put($filePath, 'Your UnoPim App is Successfully Installed');

        Event::dispatch('unopim.installed');

        return $filePath;
    }
}
