<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Webkul\Core\ElasticSearch;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as UnoPimDatabaseSeeder;
use Webkul\Installer\Events\ComposerEvents;
use Webkul\Installer\Helpers\DemoDataInstaller;

use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\password;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Installer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:install
        { --skip-env-check : Skip env check. }
        { --skip-admin-creation : Skip admin creation. }
        { --with-demo-data : Seed sample products and demo data. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'UnoPim installer.';

    /**
     * Locales list.
     *
     * @var array
     */
    protected $locales = [
        'ar_AE' => 'Arabic (United Arab Emirates)',
        'ca_ES' => 'Catalan (Spain)',
        'da_DK' => 'Danish (Denmark)',
        'de_DE' => 'German (Germany)',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GB' => 'English (United Kingdom)',
        'en_NZ' => 'English (New Zealand)',
        'en_US' => 'English (United States)',
        'es_ES' => 'Spanish (Spain)',
        'es_VE' => 'Spanish (Venezuela)',
        'fi_FI' => 'Finnish (Finland)',
        'fr_CA' => 'French (Canada)',
        'fr_FR' => 'French (France)',
        'hi_IN' => 'Hindi (India)',
        'hr_HR' => 'Croatian (Croatia)',
        'it_IT' => 'Italian (Italy)',
        'ja_JP' => 'Japanese (Japan)',
        'ko_KR' => 'Korean (South Korea)',
        'nl_NL' => 'Dutch (Netherlands)',
        'no_NO' => 'Norwegian (Norway)',
        'pl_PL' => 'Polish (Poland)',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'ro_RO' => 'Romanian (Romania)',
        'ru_RU' => 'Russian (Russia)',
        'sv_SE' => 'Swedish (Sweden)',
        'tl_PH' => 'Tagalog (Philippines)',
        'tr_TR' => 'Turkish (Turkey)',
        'uk_UA' => 'Ukrainian (Ukraine)',
        'vi_VN' => 'Vietnamese (Vietnam)',
        'zh_CN' => 'Chinese (China)',
        'zh_TW' => 'Chinese (Taiwan)',
    ];

    /**
     * Currencies list.
     *
     * @var array
     */
    protected $currencies = [
        'CAD' => 'Canadian Dollar',
        'CNY' => 'Chinese Yuan',
        'AED' => 'Dirham',
        'EUR' => 'Euro',
        'INR' => 'Indian Rupee',
        'IRR' => 'Iranian Rial',
        'AFN' => 'Israeli Shekel',
        'JPY' => 'Japanese Yen',
        'GBP' => 'Pound Sterling',
        'RUB' => 'Russian Ruble',
        'SAR' => 'Saudi Riyal',
        'TRY' => 'Turkish Lira',
        'USD' => 'US Dollar',
        'UAH' => 'Ukrainian Hryvnia',
    ];

    /**
     * Install and configure UnoPIm.
     */
    public function handle()
    {
        $applicationDetails = ! $this->option('skip-env-check')
            ? $this->checkForEnvFile()
            : [];

        $this->loadEnvConfigAtRuntime();

        $this->warn('Step: Generating key...');
        $this->call('key:generate');

        if (config('elasticsearch.enabled') == 'true') {
            $this->warn('Step: Testing ElasticSearch Connection...');
            if (! ElasticSearch::testConnection()) {
                $this->error('Verify that the correct credentials are provided to establish a connection with ElasticSearch.');

                return;
            } else {
                $this->info('Elasticsearch Connected successfully');
            }
        }

        $this->warn('Step: Migrating all tables...');
        $this->call('migrate:fresh');

        $this->warn('Step: Seeding basic data for UnoPim kickstart...');
        $this->info(app(UnoPimDatabaseSeeder::class)->run([
            'default_locale'     => $applicationDetails['default_locale'] ?? 'en_US',
            'allowed_locales'    => $applicationDetails['allowed_locales'] ?? ['en_US'],
            'default_currency'   => $applicationDetails['default_currency'] ?? 'USD',
            'allowed_currencies' => $applicationDetails['allowed_currencies'] ?? ['USD'],
        ]));

        $this->warn('Step: Linking storage directory...');
        $this->call('storage:link');

        if (config('elasticsearch.enabled') == 'true') {
            $this->warn('Step: Clearing elasticsearch index...');
            $this->call('unopim:elastic:clear');

            $this->warn('Step: Indexing categories to Elasticsearch...');
            $this->call('unopim:category:index');

            $this->warn('Step: Indexing products to Elasticsearch...');
            $this->call('unopim:product:index');
        }

        $this->warn('Step: Clearing cached bootstrap files...');
        $this->call('optimize:clear');

        if (! $this->option('skip-admin-creation')) {
            $this->warn('Step: Create admin credentials...');
            $this->createAdminCredentials();
        }

        if ($this->option('with-demo-data')) {
            $this->seedSampleProducts();
        }

        $this->markInstalled();

        ComposerEvents::postCreateProject();
    }

    /**
     * Write the completion marker that seals the installer.
     *
     * Once `storage/installed` exists, the `CanInstall` middleware redirects
     * every `/install` request and the installer controller's guard blocks the
     * api endpoints. Guarded so the marker is written — and `unopim.installed`
     * dispatched — exactly once, no matter which install steps ran.
     */
    protected function markInstalled(): void
    {
        if (file_exists(storage_path('installed'))) {
            return;
        }

        File::put(storage_path('installed'), 'UnoPim installation completed successfully');

        Event::dispatch('unopim.installed');
    }

    /**
     *  Checking .env file and if not found then create .env file.
     *
     * @return ?array
     */
    protected function checkForEnvFile()
    {
        if (! file_exists(base_path('.env'))) {
            $this->info('Creating the environment configuration file.');

            File::copy('.env.example', '.env');
        } else {
            $this->info('Great! your environment configuration file already exists.');
        }

        return $this->createEnvFile();
    }

    /**
     * Create a new .env file. Afterwards, request environment configuration details and set them
     * in the .env file to facilitate the migration to our database.
     *
     * @return ?array
     */
    protected function createEnvFile()
    {
        try {
            $applicationDetails = $this->askForApplicationDetails();

            $this->askForDatabaseDetails();

            $this->askForElasticSearchDetails();

            return $applicationDetails;
        } catch (\Exception $e) {
            $this->error('Error in creating .env file, please create it manually and then run `php artisan migrate` again.');
        }
    }

    /**
     * Ask for application details.
     *
     * @return void
     */
    protected function askForApplicationDetails()
    {
        $this->updateEnvVariable(
            'APP_NAME',
            'Please provide the name of the application',
            env('APP_NAME', 'UnoPim')
        );

        $this->updateEnvVariable(
            'APP_URL',
            'Please provide the application URL',
            env('APP_URL', 'http://localhost:8000')
        );

        $this->envUpdate(
            'APP_TIMEZONE',
            date_default_timezone_get()
        );

        $this->info('Your Default Timezone is '.date_default_timezone_get());

        $defaultLocale = $this->updateEnvChoice(
            'APP_LOCALE',
            'Please select the default application locale',
            $this->locales
        );

        $defaultCurrency = $this->updateEnvChoice(
            'APP_CURRENCY',
            'Please select the default currency',
            $this->currencies
        );

        $allowedLocales = $this->allowedChoice(
            'Please choose the allowed locales for your channels',
            $this->locales
        );

        $allowedCurrencies = $this->allowedChoice(
            'Please choose the allowed currencies for your channels',
            $this->currencies
        );

        $allowedLocales = array_values(array_unique(array_merge(
            [$defaultLocale],
            array_keys($allowedLocales)
        )));

        $allowedCurrencies = array_values(array_unique(array_merge(
            [$defaultCurrency ?? 'USD'],
            array_keys($allowedCurrencies)
        )));

        return [
            'default_locale'     => $defaultLocale,
            'allowed_locales'    => $allowedLocales,
            'default_currency'   => $defaultCurrency,
            'allowed_currencies' => $allowedCurrencies,
        ];
    }

    /**
     * Add the database credentials to the .env file.
     */
    protected function askForDatabaseDetails()
    {
        $databaseDetails = [
            'DB_CONNECTION' => select(
                'Please select the database connection',
                ['mysql', 'pgsql', 'sqlsrv']
            ),

            'DB_HOST'       => text(
                label: 'Please enter the database host',
                default: env('DB_HOST') ?? '127.0.0.1',
                required: true,
                validate: fn (string $value) => preg_match('/\s/', trim($value))
                    ? 'The database host cannot contain whitespace.'
                    : null,
                transform: trim(...),
            ),

            'DB_PORT'       => text(
                label: 'Please enter the database port',
                default: env('DB_PORT') ?? '3306',
                required: true,
                validate: fn (string $value) => ctype_digit(trim($value))
                    ? null
                    : 'The database port must be numeric.',
                transform: trim(...),
            ),

            'DB_DATABASE' => text(
                label: 'Please enter the database name',
                default: env('DB_DATABASE') ?? '',
                required: true,
                validate: function (string $value): ?string {
                    $trimmed = trim($value);

                    return match (true) {
                        $trimmed === ''                                 => 'The database name is required.',
                        (bool) preg_match('/[^A-Za-z0-9_]/', $trimmed)  => 'The database name can only contain letters, numbers, and underscores. Characters like dots, dashes, and spaces are not allowed because they break SQL identifier quoting.',
                        default                                         => null,
                    };
                },
                transform: trim(...),
            ),

            'DB_PREFIX' => text(
                label: 'Please enter the database prefix',
                default: env('DB_PREFIX') ?? '',
                validate: function (string $value): ?string {
                    $trimmed = trim($value);

                    return match (true) {
                        (bool) preg_match('/\s/', $trimmed)             => 'The database prefix cannot contain spaces.',
                        strlen($trimmed) > 4                            => 'The database prefix should not exceed 4 characters.',
                        (bool) preg_match('/[^a-zA-Z0-9_]/', $trimmed)  => 'The database prefix can only contain letters, numbers, and underscores.',
                        default                                         => null,
                    };
                },
                transform: trim(...),
                hint: 'or press enter to continue (leave empty to clear)'
            ),

            'DB_USERNAME' => text(
                label: 'Please enter your database username',
                default: env('DB_USERNAME') ?? '',
                required: true,
                validate: fn (string $value) => preg_match('/\s/', trim($value))
                    ? 'The database username cannot contain whitespace.'
                    : null,
                transform: trim(...),
            ),

            'DB_PASSWORD' => password(
                label: 'Please enter your database password',
                required: true
            ),
        ];

        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_PREFIX', 'DB_USERNAME'] as $trimKey) {
            $databaseDetails[$trimKey] = trim((string) ($databaseDetails[$trimKey] ?? ''));
        }

        if (
            $databaseDetails['DB_DATABASE'] === ''
            || $databaseDetails['DB_USERNAME'] === ''
            || $databaseDetails['DB_PASSWORD'] === ''
        ) {
            return $this->error('Please enter the database credentials.');
        }

        foreach ($databaseDetails as $key => $value) {
            if ($value || $key === 'DB_PREFIX') {
                $this->envUpdate($key, $value);
            }
        }
    }

    /**
     * Add the Elasticsearch credentials to the .env file.
     */
    protected function askForElasticSearchDetails()
    {
        $isElasticEnabled = select(
            label: 'Do you want to enable Elasticsearch?',
            options: ['yes', 'no'],
            default: env('ELASTICSEARCH_ENABLED') ?? 'false'
        ) === 'yes';

        if (! $isElasticEnabled) {
            $this->envUpdate('ELASTICSEARCH_ENABLED', 'false');

            return;
        }

        $this->envUpdate('ELASTICSEARCH_ENABLED', 'true');

        $connectionType = select(
            label: 'Please select the Elasticsearch connection',
            options: ['default', 'api', 'cloud'],
            default: env('ELASTICSEARCH_CONNECTION') ?? 'default'
        );

        $this->envUpdate('ELASTICSEARCH_CONNECTION', $connectionType);

        if ($connectionType === 'cloud') {
            $cloudId = text(
                label: 'Please enter your Elasticsearch Cloud ID',
                default: env('ELASTICSEARCH_CLOUD_ID') ?? '',
                transform: trim(...),
            );
            $this->envUpdate('ELASTICSEARCH_CLOUD_ID', $cloudId);
        } else {
            $host = text(
                label: 'Please enter the Elasticsearch host',
                default: env('ELASTICSEARCH_HOST') ?? '127.0.0.1:9200',
                transform: trim(...),
            );
            $this->envUpdate('ELASTICSEARCH_HOST', $host);

            $user = text(
                label: 'Please enter the Elasticsearch user',
                default: env('ELASTICSEARCH_USER') ?? '',
                transform: trim(...),
            );
            $this->envUpdate('ELASTICSEARCH_USER', $user);

            $password = password(
                label: 'Please enter the Elasticsearch password'
            );
            $this->envUpdate('ELASTICSEARCH_PASS', $password);

            if ($connectionType === 'api') {
                $apiKey = text(
                    label: 'Please enter the Elasticsearch API key',
                    default: env('ELASTICSEARCH_API_KEY') ?? '',
                    transform: trim(...),
                );
                $this->envUpdate('ELASTICSEARCH_API_KEY', $apiKey);
            }
        }

        $indexPrefix = text(
            label: 'Please enter your Elasticsearch Index Prefix',
            default: env('ELASTICSEARCH_INDEX_PREFIX') ?? '',
            transform: trim(...),
        );

        $this->envUpdate('ELASTICSEARCH_INDEX_PREFIX', $indexPrefix);
    }

    /**
     * Create a admin credentials.
     *
     * @return mixed
     */
    protected function createAdminCredentials()
    {
        $adminName = text(
            label: 'Set the Name for Administrator',
            default  : 'Example',
            required: true,
            transform: trim(...),
        );

        $adminEmail = text(
            label: 'Provide Email of Administrator',
            default  : 'admin@example.com',
            validate: fn (string $value) => match (true) {
                ! filter_var(trim($value), FILTER_VALIDATE_EMAIL) => 'The email address you entered is not valid please try again.',
                default                                           => null
            },
            transform: trim(...),
        );

        $adminPassword = password(
            label: 'Input a Password for Administrator',
            required: true,
            hint: 'Minimum 6 characters',
        );

        while (strlen($adminPassword) < 6) {
            $this->error('Password must be at least 6 characters.');

            $adminPassword = password(
                label: 'Input a Secure Password for Administrator',
                required: true,
            );
        }

        $password = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        try {
            DB::table('admins')->updateOrInsert(
                ['id' => 1],
                [
                    'name'     => $adminName,
                    'email'    => $adminEmail,
                    'password' => $password,
                    'role_id'  => 1,
                    'status'   => 1,
                    'timezone' => config('app.timezone') ?: 'UTC',
                ]
            );

            if (select(
                label: 'Do you want sample products?',
                options: ['yes', 'no'],
                default: 'no'
            ) === 'yes') {
                $this->seedSampleProducts();
            }

            $filePath = storage_path('installed');

            File::put($filePath, 'UnoPim installation completed successfully');

            $this->info('-----------------------------');
            $this->info('Great job, you\'ve done it!');
            $this->info('Congratulations! The installation has successfully completed and UnoPim is ready for use.');
            $this->info('Please navigate to: '.env('APP_URL').'/admin'.' and use the following credentials for authentication:');
            $this->info('Email: '.$adminEmail);
            $this->info('Password was securely set for the admin user.');
            $this->info('Cheers!');

            Event::dispatch('unopim.installed');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function seedSampleProducts(): void
    {
        $result = app(DemoDataInstaller::class)
            ->seed(fn (string $message) => $this->warn('Step: '.$message));

        if ($result['success']) {
            $this->info('Sample products seeded successfully.');
        } else {
            $this->error("Failed to seed sample products: {$result['error']}");
        }
    }

    /**
     * Loaded Env variables for config files.
     */
    protected function loadEnvConfigAtRuntime(): void
    {
        $this->warn('Loading configs...');

        /**
         * Setting application environment.
         */
        app()['env'] = $this->getEnvAtRuntime('APP_ENV');

        /**
         * Setting application configuration.
         */
        config([
            'app.env'      => $this->getEnvAtRuntime('APP_ENV'),
            'app.name'     => $this->getEnvAtRuntime('APP_NAME'),
            'app.url'      => $this->getEnvAtRuntime('APP_URL'),
            'app.timezone' => $this->getEnvAtRuntime('APP_TIMEZONE'),
            'app.locale'   => $this->getEnvAtRuntime('APP_LOCALE'),
            'app.currency' => $this->getEnvAtRuntime('APP_CURRENCY'),
        ]);

        /**
         * Setting database configurations.
         */
        $databaseConnection = $this->getEnvAtRuntime('DB_CONNECTION');

        $previousDefault = config('database.default');

        config([
            'database.default'                                    => $databaseConnection,
            "database.connections.{$databaseConnection}.host"     => $this->getEnvAtRuntime('DB_HOST'),
            "database.connections.{$databaseConnection}.port"     => $this->getEnvAtRuntime('DB_PORT'),
            "database.connections.{$databaseConnection}.database" => $this->getEnvAtRuntime('DB_DATABASE'),
            "database.connections.{$databaseConnection}.username" => $this->getEnvAtRuntime('DB_USERNAME'),
            "database.connections.{$databaseConnection}.password" => $this->getEnvAtRuntime('DB_PASSWORD'),
            "database.connections.{$databaseConnection}.prefix"   => $this->getEnvAtRuntime('DB_PREFIX'),
        ]);

        DB::setDefaultConnection($databaseConnection);

        if ($previousDefault && $previousDefault !== $databaseConnection) {
            DB::purge($previousDefault);
        }

        DB::purge($databaseConnection);

        /**
         * Setting elasticsearch configurations.
         */
        $elasticsearchPrefix = $this->getEnvAtRuntime('ELASTICSEARCH_INDEX_PREFIX') != '' ? $this->getEnvAtRuntime('ELASTICSEARCH_INDEX_PREFIX') : $this->getEnvAtRuntime('APP_NAME');

        config([
            'elasticsearch.connection'                => $this->getEnvAtRuntime('ELASTICSEARCH_CONNECTION'),
            'elasticsearch.enabled'                   => $this->getEnvAtRuntime('ELASTICSEARCH_ENABLED'),
            'elasticsearch.prefix'                    => $elasticsearchPrefix,
            'elasticsearch.connections.default.hosts' => [$this->getEnvAtRuntime('ELASTICSEARCH_HOST')],
            'elasticsearch.connections.default.user'  => $this->getEnvAtRuntime('ELASTICSEARCH_USER'),
            'elasticsearch.connections.default.pass'  => $this->getEnvAtRuntime('ELASTICSEARCH_PASS'),
            'elasticsearch.connections.api.hosts'     => [$this->getEnvAtRuntime('ELASTICSEARCH_HOST')],
            'elasticsearch.connections.api.key'       => $this->getEnvAtRuntime('ELASTICSEARCH_API_KEY'),
            'elasticsearch.connections.cloud.api_key' => $this->getEnvAtRuntime('ELASTICSEARCH_API_KEY'),
            'elasticsearch.connections.cloud.id'      => $this->getEnvAtRuntime('ELASTICSEARCH_CLOUD_ID'),
            'elasticsearch.connections.cloud.user'    => $this->getEnvAtRuntime('ELASTICSEARCH_USER'),
            'elasticsearch.connections.cloud.pass'    => $this->getEnvAtRuntime('ELASTICSEARCH_PASS'),
        ]);

        $this->info('Configuration loaded...');
    }

    /**
     * Method for asking the details of .env files
     */
    protected function updateEnvVariable(string $key, string $question, string $defaultValue): void
    {
        $input = text(
            label: $question,
            default: $defaultValue,
            required: true,
            transform: trim(...),
        );

        $this->envUpdate($key, $input === '' ? $defaultValue : $input);
    }

    /**
     * Method for asking choice based on the list of options.
     *
     * @return string
     */
    protected function updateEnvChoice(string $key, string $question, array $choices)
    {
        $choice = search(
            label: $question,
            options: fn (string $value) => $this->filterChoices($choices, $value),
            placeholder: 'Type to search...',
            scroll: 10,
        );

        $this->envUpdate($key, $choice);

        return $choice;
    }

    /**
     * Function for getting allowed choices based on the list of options.
     */
    protected function allowedChoice(string $question, array $choices)
    {
        $selectedKeys = multisearch(
            label: $question,
            options: fn (string $value) => $this->filterChoices($choices, $value),
            placeholder: 'Type to search...',
            scroll: 10,
            hint: 'Use the space bar to select options.',
        );

        $selectedChoices = [];

        foreach ($selectedKeys as $selectedKey) {
            if (isset($choices[$selectedKey])) {
                $selectedChoices[$selectedKey] = $choices[$selectedKey];
            }
        }

        return $selectedChoices;
    }

    /**
     * Filter the given choices by the user's search query.
     *
     * Matches against both the option key (e.g. "en_US", "USD") and the
     * human-readable label (e.g. "English (United States)", "US Dollar").
     */
    protected function filterChoices(array $choices, string $value): array
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return $choices;
        }

        return array_filter(
            $choices,
            fn (string $label, string $key) => str_contains(strtolower($label), $value)
                || str_contains(strtolower($key), $value),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Update the .env values.
     */
    protected function envUpdate(string $key, string $value): void
    {
        $data = file_get_contents(base_path('.env'));

        // Check if $value contains spaces, and if so, add double quotes
        if (preg_match('/\s/', $value)) {
            $value = '"'.$value.'"';
        }

        $data = preg_replace("/$key=(.*)/", "$key=$value", $data);

        file_put_contents(base_path('.env'), $data);
    }

    /**
     * Check key in `.env` file because it will help to find values at runtime.
     */
    protected static function getEnvAtRuntime(string $key): string|bool
    {
        if ($data = file(base_path('.env'))) {
            foreach ($data as $line) {
                $line = preg_replace('/\s+/', '', $line);

                $rowValues = explode('=', $line);

                if (strlen($line) !== 0) {
                    if (strpos($key, $rowValues[0]) !== false) {
                        return $rowValues[1];
                    }
                }
            }
        }

        return false;
    }
}
