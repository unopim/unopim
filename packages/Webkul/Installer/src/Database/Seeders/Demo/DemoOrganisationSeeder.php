<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Seeds the people side of the demo: the roles a catalog team is normally
 * split into, a user per role, the webhook and publication settings an
 * operator is expected to configure, and sample webhook endpoints.
 *
 * Every demo user shares one password so the credentials can be printed with
 * the install; the accounts exist to show the ACL, not to be secure.
 */
class DemoOrganisationSeeder extends Seeder
{
    use LoadsDemoData;

    public const PASSWORD = 'demo1234';

    public const AVATAR_PATH = 'demo/avatars';

    public function run(): void
    {
        $data = $this->demoData('organisation');

        $this->publishAvatars();

        DB::transaction(function () use ($data): void {
            $roleIds = $this->seedRoles($data['roles']);

            $this->seedUsers($data['users'], $roleIds);

            $this->seedWebhooks($data['webhooks']);

            $this->seedSettings($data['settings']);
        });
    }

    protected function publishAvatars(): void
    {
        $source = realpath(__DIR__.'/../../../Resources/assets/images/demo/avatars');

        if ($source === false || ! File::isDirectory($source)) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (File::files($source) as $file) {
            $disk->put(self::AVATAR_PATH.'/'.$file->getFilename(), File::get($file->getRealPath()));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $roles
     * @return array<string, int>
     */
    protected function seedRoles(array $roles): array
    {
        $now = Date::now();
        $ids = [];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'description'     => $role['description'],
                    'permission_type' => $role['permission_type'],
                    'permissions'     => isset($role['permissions'])
                        ? json_encode($role['permissions'], JSON_THROW_ON_ERROR)
                        : null,
                    'updated_at'      => $now,
                    'created_at'      => $now,
                ],
            );

            $ids[$role['name']] = (int) DB::table('roles')->where('name', $role['name'])->value('id');
        }

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<string, int>  $roleIds
     */
    protected function seedUsers(array $users, array $roleIds): void
    {
        $now = Date::now();

        $localeId = (int) DB::table('locales')->where('code', 'en_US')->value('id');
        $channelId = (int) DB::table('channels')->where('code', 'ecommerce')->value('id');
        $disk = Storage::disk('public');

        foreach ($users as $user) {
            $roleId = $roleIds[$user['role']] ?? null;

            if (! $roleId) {
                continue;
            }

            $avatar = self::AVATAR_PATH.'/'.$user['avatar'].'.webp';

            DB::table('admins')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name'               => $user['name'],
                    'password'           => Hash::make(self::PASSWORD),
                    'status'             => 1,
                    'type'               => 'user',
                    'role_id'            => $roleId,
                    'timezone'           => $user['timezone'],
                    'use_gravatar'       => false,
                    'image'              => $disk->exists($avatar) ? $avatar : null,
                    'ui_locale_id'       => $localeId ?: null,
                    'catalog_locale_id'  => $localeId ?: null,
                    'default_channel_id' => $channelId ?: null,
                    'updated_at'         => $now,
                    'created_at'         => $now,
                ],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $webhooks
     */
    protected function seedWebhooks(array $webhooks): void
    {
        $now = Date::now();

        foreach ($webhooks as $webhook) {
            DB::table('webhooks')->updateOrInsert(
                ['name' => $webhook['name']],
                [
                    'url'        => $webhook['url'],
                    'is_active'  => $webhook['active'],
                    'events'     => json_encode($webhook['events'], JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function seedSettings(array $settings): void
    {
        $now = Date::now();

        foreach ($settings as $code => $value) {
            DB::table('core_config')->updateOrInsert(
                ['code' => $code, 'channel_code' => null, 'locale_code' => null],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
