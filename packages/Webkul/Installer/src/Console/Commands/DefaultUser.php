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
        'ar_AE' => 'Arabic',
        'de_DE' => 'German',
        'en_US' => 'English',
        'es_ES' => 'Spanish',
        'fr_FR' => 'French',
        'hi_IN' => 'Hindi',
        'ja_JP' => 'Japanese',
        'nl_NL' => 'Dutch',
        'ru_RU' => 'Russian',
        'zh_CN' => 'Chinese',
    ];

    /**
     * Create UnoPim user.
     */
    public function handle()
    {
        $userName = $this->option('name') ?: text(
            label: 'Set the Name for User',
            default: 'Admin',
            required: true
        );

        $userEmail = $this->option('email');

        if (! $userEmail || ! filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $userEmail = text(
                label: 'Provide Email of User',
                default: 'admin@example.com',
                validate: fn (string $value) => match (true) {
                    ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The provided email is invalid, kindly enter a valid email address.',
                    default                                     => null
                }
            );
        }

        $userPassword = $this->option('password') ?: text(
            label: 'Input a Secure Password for User',
            default: 'admin@123',
            required: true
        );

        while (strlen($userPassword) < 6) {
            $this->error('Password must be at least 6 characters.');

            $userPassword = text(
                label: 'Input a Secure Password for User',
                default: 'admin@123',
                required: true
            );
        }

        $password = password_hash($userPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        $timezone = $this->option('timezone') ?? date_default_timezone_get();

        $this->info('Your Default Timezone is '.$timezone);

        $defaultLocale = $this->option('ui_locale') ?: $this->askForDefaultLocale(
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
                    'description'     => $isAdmin ? 'This role users will have all the access' : 'This role users have limited access',
                    'permission_type' => $isAdmin ? 'all' : 'custom',
                    'permissions'     => ! $isAdmin ? json_encode(['dashboard']) : null,
                ]
            );
        }

        $role = $isAdmin ? DB::table('roles')->where('permission_type', 'all')->first()?->id : DB::table('roles')->where('permission_type', 'custom')->first()?->id;

        try {
            DB::table('admins')->updateOrInsert(
                [
                    'api_token'    => Str::random(80),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'name'         => $userName,
                    'email'        => $userEmail,
                    'password'     => $password,
                    'role_id'      => $role,
                    'status'       => 1,
                    'timezone'     => $timezone,
                    'ui_locale_id' => $localeId,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]
            );

            $this->info('-----------------------------');
            $this->info('Congratulations! The User has been created successfully.');
            $this->info('Please navigate to: '.env('APP_URL').'/admin'.' and use the following credentials for authentication:');
            $this->info('Email: '.$userEmail);
            $this->info('Password: '.$userPassword);
            $this->info('Cheers!');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry')) {
                $this->error('User with email '.$userEmail.' already exists.');
            } else {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * Method for asking default locale choice based on the list of options.
     */
    protected function askForDefaultLocale(string $key, string $question, array $choices): string
    {
        $choice = select(
            label: $question,
            options: $choices,
            default: env($key)
        );

        return $choice;
    }
}
