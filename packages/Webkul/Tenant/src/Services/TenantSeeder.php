<?php

namespace Webkul\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webkul\Tenant\Mail\TenantWelcomeMail;
use Webkul\Tenant\Models\Tenant;

class TenantSeeder
{
    /**
     * Seed all baseline data for a newly provisioned tenant.
     *
     * Runs inside a DB transaction — partial seeding is rolled back on failure.
     *
     * @return array Seeded entity IDs and credentials
     */
    public function seed(Tenant $tenant, array $options = []): array
    {
        return DB::transaction(function () use ($tenant, $options) {
            $localeCode = $options['locale'] ?? 'en_US';
            $currencyCode = strtoupper($options['currency'] ?? 'USD');
            $email = $options['email'] ?? "admin@{$tenant->domain}.test";
            $password = $options['password'] ?? Str::random(12);
            $now = now();

            // 1. Create locale
            $localeId = DB::table('locales')->insertGetId([
                'code'      => $localeCode,
                'status'    => 1,
                'tenant_id' => $tenant->id,
            ]);

            // 2. Create currency
            $currencyId = DB::table('currencies')->insertGetId([
                'code'      => $currencyCode,
                'symbol'    => $this->currencySymbol($currencyCode),
                'status'    => 1,
                'tenant_id' => $tenant->id,
            ]);

            // 3. Create admin role
            $roleId = DB::table('roles')->insertGetId([
                'name'            => 'Administrator',
                'description'     => 'Full access administrator role',
                'permission_type' => 'all',
                'permissions'     => json_encode([]),
                'tenant_id'       => $tenant->id,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            // 4. Create admin user
            $adminId = DB::table('admins')->insertGetId([
                'name'         => $options['admin_name'] ?? 'Administrator',
                'email'        => $email,
                'password'     => bcrypt($password),
                'role_id'      => $roleId,
                'status'       => 1,
                'ui_locale_id' => $localeId,
                'timezone'     => $options['timezone'] ?? 'UTC',
                'tenant_id'    => $tenant->id,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // 5. Create root category
            $categoryId = DB::table('categories')->insertGetId([
                'code'            => 'root',
                'parent_id'       => null,
                '_lft'            => 1,
                '_rgt'            => 2,
                'tenant_id'       => $tenant->id,
                'additional_data' => json_encode([
                    'locale_specific' => [
                        $localeCode => ['name' => 'Root'],
                    ],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 6. Create default channel + translations + pivots
            $channelId = DB::table('channels')->insertGetId([
                'code'             => 'default',
                'root_category_id' => $categoryId,
                'tenant_id'        => $tenant->id,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            DB::table('channel_translations')->insert([
                'channel_id' => $channelId,
                'locale'     => $localeCode,
                'name'       => 'Default',
            ]);

            DB::table('channel_locales')->insert([
                'channel_id' => $channelId,
                'locale_id'  => $localeId,
            ]);

            DB::table('channel_currencies')->insert([
                'channel_id'  => $channelId,
                'currency_id' => $currencyId,
            ]);

            // 7. Create default attribute family + translation
            $familyId = DB::table('attribute_families')->insertGetId([
                'code'      => 'default',
                'status'    => 0,
                'tenant_id' => $tenant->id,
            ]);

            DB::table('attribute_family_translations')->insert([
                'attribute_family_id' => $familyId,
                'locale'              => $localeCode,
                'name'                => 'Default',
            ]);

            // 8. Create API key (OAuth client + api_keys record)
            $apiKeyResult = $this->createApiKey($tenant, $adminId);

            // 9. Send welcome email (non-blocking — failure doesn't abort seeding)
            $this->sendWelcomeEmail($email, $password, $tenant);

            return [
                'role_id'          => $roleId,
                'locale_id'        => $localeId,
                'currency_id'      => $currencyId,
                'admin_id'         => $adminId,
                'admin_email'      => $email,
                'admin_password'   => $password,
                'root_category_id' => $categoryId,
                'channel_id'       => $channelId,
                'family_id'        => $familyId,
                'api_key_id'       => $apiKeyResult['api_key_id'],
                'client_id'        => $apiKeyResult['client_id'],
                'client_secret'    => $apiKeyResult['client_secret'],
            ];
        });
    }

    private function createApiKey(Tenant $tenant, int $adminId): array
    {
        $plainSecret = Str::random(40);
        $now = now();

        $clientId = DB::table('oauth_clients')->insertGetId([
            'user_id'                => $adminId,
            'name'                   => $tenant->name.' API',
            'secret'                 => hash('sha256', $plainSecret),
            'redirect'               => 'http://localhost',
            'personal_access_client' => false,
            'password_client'        => true,
            'revoked'                => false,
            'provider'               => 'admins',
            'tenant_id'              => $tenant->id,
            'created_at'             => $now,
            'updated_at'             => $now,
        ]);

        $apiKeyId = DB::table('api_keys')->insertGetId([
            'name'            => $tenant->name.' API Key',
            'admin_id'        => $adminId,
            'oauth_client_id' => $clientId,
            'permission_type' => 'all',
            'permissions'     => json_encode([]),
            'revoked'         => false,
            'tenant_id'       => $tenant->id,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return [
            'api_key_id'    => $apiKeyId,
            'client_id'     => $clientId,
            'client_secret' => $plainSecret,
        ];
    }

    private function sendWelcomeEmail(string $email, string $password, Tenant $tenant): void
    {
        try {
            Mail::to($email)->send(new TenantWelcomeMail($tenant, $email, $password));
        } catch (\Throwable $e) {
            // Email failure should not block provisioning
            report($e);
        }
    }

    private function currencySymbol(string $code): string
    {
        $symbols = [
            'USD' => '$',  'EUR' => "\u{20AC}", 'GBP' => "\u{00A3}", 'JPY' => "\u{00A5}",
            'INR' => "\u{20B9}", 'AUD' => 'A$', 'CAD' => 'C$', 'CHF' => 'CHF',
            'CNY' => "\u{00A5}", 'BRL' => 'R$', 'KRW' => "\u{20A9}", 'MXN' => 'MX$',
        ];

        return $symbols[$code] ?? $code;
    }
}
