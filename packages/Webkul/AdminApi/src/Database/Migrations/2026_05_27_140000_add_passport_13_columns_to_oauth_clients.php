<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\User\Models\Admin;

/**
 * Bring the legacy oauth_clients schema (Passport 12) up to date with
 * Passport 13's expected columns:
 *   - nullableMorphs('owner') → owner_type + owner_id
 *   - redirect_uris (text JSON, replaces single 'redirect')
 *   - grant_types  (text JSON, list of OAuth grants the client supports)
 *
 * Legacy user_id, redirect, personal_access_client and password_client
 * columns are KEPT so existing UnoPim integrations (ApiKeysDataGrid JOIN,
 * older customer queries) keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('oauth_clients', 'owner_type')) {
                $table->string('owner_type')->nullable()->after('id');
            }
            if (! Schema::hasColumn('oauth_clients', 'owner_id')) {
                $table->string('owner_id')->nullable()->after('owner_type');
                $table->index(['owner_type', 'owner_id']);
            }
            if (! Schema::hasColumn('oauth_clients', 'redirect_uris')) {
                $table->text('redirect_uris')->nullable()->after('provider');
            }
            if (! Schema::hasColumn('oauth_clients', 'grant_types')) {
                $table->text('grant_types')->nullable()->after('redirect_uris');
            }
        });

        // Passport 13's createPasswordGrantClient() no longer writes the
        // legacy `redirect`, `personal_access_client`, or `password_client`
        // columns. PostgreSQL strictly enforces their NOT NULL constraints
        // and rejects every INSERT (MySQL silently defaults). Loosen them
        // to nullable so Passport 13 INSERTs succeed on every supported DB.
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->text('redirect')->nullable()->change();
            $table->boolean('personal_access_client')->nullable()->change();
            $table->boolean('password_client')->nullable()->change();
        });

        // Backfill: copy legacy user_id into owner_type/owner_id morph,
        // serialise legacy 'redirect' column into the new redirect_uris JSON,
        // and supply a sensible default grant_types list for existing clients.
        $adminClass = Admin::class;

        DB::table('oauth_clients')
            ->whereNotNull('user_id')
            ->whereNull('owner_id')
            ->update([
                'owner_type' => $adminClass,
                'owner_id'   => DB::raw('user_id'),
            ]);

        DB::table('oauth_clients')
            ->whereNull('redirect_uris')
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows) {
                foreach ($rows as $row) {
                    DB::table('oauth_clients')
                        ->where('id', $row->id)
                        ->update([
                            'redirect_uris' => json_encode([$row->redirect ?? 'http://localhost']),
                            'grant_types'   => json_encode(['password', 'refresh_token']),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            if (Schema::hasColumn('oauth_clients', 'owner_id')) {
                $table->dropIndex(['owner_type', 'owner_id']);
                $table->dropColumn('owner_id');
            }
            if (Schema::hasColumn('oauth_clients', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
            if (Schema::hasColumn('oauth_clients', 'redirect_uris')) {
                $table->dropColumn('redirect_uris');
            }
            if (Schema::hasColumn('oauth_clients', 'grant_types')) {
                $table->dropColumn('grant_types');
            }
        });
    }
};
