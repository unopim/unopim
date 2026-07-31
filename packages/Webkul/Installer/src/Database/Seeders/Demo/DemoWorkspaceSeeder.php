<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Seeds the saved product grid views and one inactive sample webhook.
 *
 * The webhook ships disabled on purpose: a demo install must not make
 * outbound requests to a placeholder endpoint.
 */
class DemoWorkspaceSeeder extends Seeder
{
    use LoadsDemoData;

    public function run(): void
    {
        $data = $this->demoData('grid_views');

        $adminId = (int) DB::table('admins')->orderBy('id')->value('id');

        if ($adminId === 0) {
            return;
        }

        DB::transaction(function () use ($data, $adminId): void {
            $this->seedGridViews($data['views'], $adminId);

            $this->seedWebhook($data['webhook']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $views
     */
    protected function seedGridViews(array $views, int $adminId): void
    {
        $now = Date::now();

        foreach ($views as $view) {
            DB::table('product_grid_views')->updateOrInsert(
                ['admin_id' => $adminId, 'name' => $view['name']],
                [
                    'is_shared'  => true,
                    'payload'    => json_encode($view['payload'], JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $webhook
     */
    protected function seedWebhook(array $webhook): void
    {
        $now = Date::now();

        DB::table('webhooks')->updateOrInsert(
            ['name' => $webhook['name']],
            [
                'url'        => $webhook['url'],
                'is_active'  => false,
                'events'     => json_encode($webhook['events'], JSON_THROW_ON_ERROR),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}
