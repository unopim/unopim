<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;
use Webkul\AdminApi\Models\Apikey;
use Webkul\AdminApi\Services\ApiUserProvisioner;
use Webkul\User\Models\Admin;

return new class extends Migration
{
    public function up(): void
    {
        $provisioner = app(ApiUserProvisioner::class);

        Apikey::where('revoked', 0)->chunkById(100, function ($keys) use ($provisioner) {
            foreach ($keys as $key) {
                $owner = Admin::find($key->admin_id);

                // Already robot-owned: nothing to do, keeps re-runs idempotent.
                if ($owner && $owner->isApiUser()) {
                    continue;
                }

                DB::transaction(function () use ($provisioner, $key) {
                    $robot = $provisioner->provisionForIntegration($key->name)['admin'];

                    $key->forceFill(['admin_id' => $robot->id])->save();

                    if ($key->oauth_client_id) {
                        Token::where('client_id', $key->oauth_client_id)->update(['revoked' => true]);
                    }
                });
            }
        });
    }

    public function down(): void
    {
        // No-op: robot reassignment is not reversible.
    }
};
