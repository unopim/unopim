<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Carry the legacy single webhook (stored as key-value rows in
     * webhook_settings) into the new per-webhook registry so existing client
     * endpoints keep firing on the same product events with the same payload.
     */
    public function up(): void
    {
        $settings = DB::table('webhook_settings')
            ->whereIn('field', ['webhook_url', 'webhook_active'])
            ->pluck('value', 'field');

        $url = $settings['webhook_url'] ?? null;

        if (in_array($url, [null, '', '0'], true)) {
            return;
        }

        if (DB::table('webhooks')->where('url', $url)->exists()) {
            return;
        }

        $name = trans('webhook::app.webhooks.index.default-name');

        if ($name === 'webhook::app.webhooks.index.default-name') {
            $name = 'Default';
        }

        $webhookId = DB::table('webhooks')->insertGetId([
            'name'       => $name,
            'url'        => $url,
            'is_active'  => (int) ($settings['webhook_active'] ?? 0) === 1 ? 1 : 0,
            'events'     => json_encode(['product.created', 'product.updated']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('webhook_logs')->whereNull('webhook_id')->update(['webhook_id' => $webhookId]);
    }

    public function down(): void
    {
        $webhookId = DB::table('webhooks')->orderBy('id')->value('id');

        if ($webhookId !== null) {
            DB::table('webhook_logs')->where('webhook_id', $webhookId)->update(['webhook_id' => null]);
            DB::table('webhooks')->where('id', $webhookId)->delete();
        }
    }
};
