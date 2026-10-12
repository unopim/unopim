<?php

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

class RolesTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        DatabaseSequenceHelper::fixSequence('roles');

        /**
         * The Administrator role must occupy id 1 — the id every admin seeder and
         * the installer hardcode as the admin's role_id. Bail only when id 1 is
         * already the full-access Administrator (a healthy install, possibly with
         * an operator-renamed role we must not overwrite). When id 1 is missing,
         * or has been claimed by another role (e.g. the API role on a fresh
         * install), (re)assert the Administrator at id 1 so the admin is never
         * left pointing at an empty-permission role.
         */
        $existing = DB::table('roles')->where('id', 1)->first();

        if ($existing && $existing->permission_type === 'all') {
            return;
        }

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        DB::table('roles')->updateOrInsert(
            ['id' => 1],
            [
                'name'            => trans('installer::app.seeders.user.roles.name', [], $defaultLocale),
                'description'     => trans('installer::app.seeders.user.roles.description', [], $defaultLocale),
                'permission_type' => 'all',
                'permissions'     => null,
            ]
        );

        DatabaseSequenceHelper::fixSequence('roles');
    }
}
