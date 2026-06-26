<?php

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

class AdminsTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {

        $adminEmail = ($parameters['admin_email'] ?? '')
            ?: env('INSTALLER_ADMIN_EMAIL')
            ?: 'admin@example.com';

        $providedPassword = ($parameters['admin_password'] ?? '')
            ?: env('INSTALLER_ADMIN_PASSWORD')
            ?: '';

        $adminPassword = $providedPassword ?: Str::random(20);
        $generatedRandom = $providedPassword === '';

        DatabaseSequenceHelper::fixSequence('admins');

        if (DB::table('admins')->exists()) {
            return;
        }

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');
        $defaultLocaleId = DB::table('locales')->where('code', $defaultLocale)->where('status', 1)->first()?->id ?? 58;

        DB::table('admins')->insert([
            'id'            => 1,
            'name'          => trans('installer::app.seeders.user.users.name', [], $defaultLocale),
            'email'         => $adminEmail,
            'password'      => bcrypt($adminPassword),
            'api_token'     => Str::random(80),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
            'status'        => 1,
            'role_id'       => 1,
            'timezone'      => config('app.timezone') ?: 'UTC',
            'ui_locale_id'  => $defaultLocaleId,
        ]);

        DatabaseSequenceHelper::fixSequence('admins');

        if ($generatedRandom) {
            $this->writeGeneratedCredentialsFile($adminEmail, $adminPassword);
        }
    }

    /**
     * Persist a one-time credentials file the operator can read on first boot.
     */
    private function writeGeneratedCredentialsFile(string $email, string $password): void
    {
        $path = storage_path('app/admin-credentials.txt');

        $body = "UnoPim — initial admin credentials (auto-generated)\n"
            ."====================================================\n"
            ."email:    {$email}\n"
            ."password: {$password}\n\n"
            ."Log in once, rotate the password, then delete this file.\n";

        if (file_put_contents($path, $body, LOCK_EX) === false) {
            throw new \RuntimeException(
                "Unable to write initial admin credentials to {$path}. ".
                'Set INSTALLER_ADMIN_PASSWORD and retry installation.'
            );
        }

        @chmod($path, 0600);

        try {
            Log::warning(
                '[unopim:install] Generated random initial admin password. '.
                'See '.$path.' — rotate and delete after first login.'
            );
        } catch (\Throwable) {
            // Logging is best-effort; never break first-time setup if the log sink is read-only.
        }
    }
}
