<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class DefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:user:create
        {--name= : The name of the user}
        {--email= : The email address of the user}
        {--password= : The password for the user}
        {--ui_locale= : The UI locale (e.g., en_US) of the user}
        {--timezone= : The timezone of the user}
        {--admin : Specify if the user is an admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command allows you to create a new user with a name, email, password, UI locale, timezone, and optionally specify whether the user is an admin.';

    /**
     * Locales list.
     *
     * @var array
     */
    protected $locales = [
        'ar_AE'       => 'Arabic',
        'de_DE'       => 'German',
        'en_US'       => 'English',
        'es_ES'       => 'Spanish',
        'fr_FR'       => 'French',
        'hi_IN'       => 'Hindi',
        'ja_JP'       => 'Japanese',
        'nl_NL'       => 'Dutch',
        'ru_RU'       => 'Russian',
        'zh_CN'       => 'Chinese',
    ];

    /**
     * Create UnoPim default user.
     */
    public function handle()
    {

        $this->loadEnvConfigAtRuntime();

        $adminName = $this->option('name') ?: text(
            label: 'Set the Name for Administrator',
            default: 'Admin',
            required: true
        );

        $adminEmail = $this->option('email');

        if (! $adminEmail || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $adminEmail = text(
                label: 'Provide Email of Administrator',
                default: 'admin@example.com',
                validate: fn (string $value) => match (true) {
                    ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The provided email is invalid, kindly enter a valid email address.',
                    default                                     => null
                }
            );
        }

        $adminPassword = $this->option('password') ?: text(
            label: 'Input a Secure Password for Administrator',
            default: 'admin@123',
            required: true
        );

        while (strlen($adminPassword) < 6) {
            $this->error('Password must be at least 6 characters.');

            $adminPassword = text(
                label: 'Input a Secure Password for Administrator',
                default: 'admin@123',
                required: true
            );
        }

        $password = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        $timezone = $this->option('timezone');

        if (! $timezone) {
            $this->envUpdate(
                'APP_TIMEZONE',
                date_default_timezone_get()
            );

            $timezone = date_default_timezone_get();
        }

        $this->info('Your Default Timezone is '.$timezone);

        $defaultLocale = $this->option('ui_locale') ?: $this->updateEnvChoice(
            'APP_LOCALE',
            'Please select the default application locale',
            $this->locales
        );

        $isAdmin = $this->option('admin');
        $localeId = DB::table('locales')->where('code', $defaultLocale)->where('status', 1)->first()?->id ?? 58;
        $role = $isAdmin ? DB::table('roles')->where('permission_type', 'all')->first()?->id : DB::table('roles')->where('permission_type', 'custom')->first()?->id;

        if (! $role) {
            DB::table('roles')->updateOrInsert(
                [
                    'name'            => $isAdmin ? 'Admin' : 'User',
                    'description'     => $isAdmin ? 'This role users will have all the access' : 'This role users will not have all the access',
                    'permission_type' => $isAdmin ? 'all' : 'custom',
                ]
            );
        }

        $role = $isAdmin ? DB::table('roles')->where('permission_type', 'all')->first()?->id : DB::table('roles')->where('permission_type', 'custom')->first()?->id;

        try {
            DB::table('admins')->updateOrInsert(
                [
                    'api_token'     => Str::random(80),
                    'created_at'    => date('Y-m-d H:i:s'),
                    'name'          => $adminName,
                    'email'         => $adminEmail,
                    'password'      => $password,
                    'role_id'       => $role,
                    'status'        => 1,
                    'timezone'      => $timezone,
                    'ui_locale_id'  => $localeId,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]
            );

            $this->info('-----------------------------');
            $this->info('Congratulations! The User has been created successfully.');
            $this->info('Please navigate to: '.env('APP_URL').'/admin'.' and use the following credentials for authentication:');
            $this->info('Email: '.$adminEmail);
            $this->info('Password: '.$adminPassword);
            $this->info('Cheers!');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry')) {
                $this->error('User with email '.$adminEmail.' already exists.');
            } else {
                $this->error($e->getMessage());
            }
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

        config([
            "database.connections.{$databaseConnection}.host"     => $this->getEnvAtRuntime('DB_HOST'),
            "database.connections.{$databaseConnection}.port"     => $this->getEnvAtRuntime('DB_PORT'),
            "database.connections.{$databaseConnection}.database" => $this->getEnvAtRuntime('DB_DATABASE'),
            "database.connections.{$databaseConnection}.username" => $this->getEnvAtRuntime('DB_USERNAME'),
            "database.connections.{$databaseConnection}.password" => $this->getEnvAtRuntime('DB_PASSWORD'),
            "database.connections.{$databaseConnection}.prefix"   => $this->getEnvAtRuntime('DB_PREFIX'),
        ]);

        DB::purge($databaseConnection);

        $this->info('Configuration loaded...');
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

    /**
     * Method for asking choice based on the list of options.
     *
     * @return string
     */
    protected function updateEnvChoice(string $key, string $question, array $choices)
    {
        $choice = select(
            label: $question,
            options: $choices,
            default: env($key)
        );

        $this->envUpdate($key, $choice);

        return $choice;
    }
}
