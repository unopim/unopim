<?php

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        DB::table('admins')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        /** Default locale id to be set to user else 58 id of en_US locale is added */
        $defaultLocaleId = DB::table('locales')->where('code', $defaultLocale)->where('status', 1)->first()?->id ?? 58;

        DB::table('admins')->insert([
            'id'            => 1,
            'name'          => trans('installer::app.seeders.user.users.name', [], $defaultLocale),
            'email'         => 'admin@example.com',
            'password'      => bcrypt('admin123'),
            'api_token'     => Str::random(80),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
            'status'        => 1,
            'role_id'       => 1,
            'ui_locale_id'  => $defaultLocaleId,
        ]);

        DatabaseSequenceHelper::fixSequence('admins');
    }
}
