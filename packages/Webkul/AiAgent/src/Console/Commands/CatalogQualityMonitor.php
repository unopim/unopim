<?php

namespace Webkul\AiAgent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled catalog quality monitor that scans for data quality issues.
 *
 * Run via: php artisan ai-agent:quality-monitor
 * Schedule: daily or as configured in the kernel.
 */
class CatalogQualityMonitor extends Command
{
    protected $signature = 'ai-agent:quality-monitor
                            {--channel=default : Channel to check}
                            {--locale=en_US : Locale to check}
                            {--limit=500 : Max products to scan}';

    protected $description = 'Scan catalog for data quality issues (missing descriptions, images, categories)';

    public function handle(): int
    {
        $agenticEnabled = core()->getConfigData('general.magic_ai.agentic_pim.enabled');

        if (! $agenticEnabled) {
            $this->info('Agentic PIM is disabled in configuration.');

            return self::SUCCESS;
        }

        $enabled = core()->getConfigData('general.magic_ai.agentic_pim.quality_monitor');

        if (! $enabled) {
            $this->info('Quality monitor is disabled in configuration.');

            return self::SUCCESS;
        }

        $channel = $this->option('channel');
        $locale = $this->option('locale');
        $limit = (int) $this->option('limit');

        $this->info("Scanning up to {$limit} products for quality issues...");

        $products = DB::table('products')
            ->select('id', 'sku', 'status', 'values')
            ->limit($limit)
            ->get();

        $issues = [
            'missing_name'        => [],
            'missing_description' => [],
            'missing_image'       => [],
            'missing_category'    => [],
            'short_description'   => [],
        ];

        foreach ($products as $p) {
            $values = json_decode($p->values, true) ?? [];
            $common = $values['common'] ?? [];
            $cl = $values['channel_locale_specific'][$channel][$locale] ?? [];

            $name = $cl['name'] ?? $common['name'] ?? null;
            $desc = $cl['description'] ?? $common['description'] ?? null;

            if (empty($name)) {
                $issues['missing_name'][] = $p->sku;
            }
            if (empty($desc)) {
                $issues['missing_description'][] = $p->sku;
            } elseif (mb_strlen($desc) < 50) {
                $issues['short_description'][] = $p->sku;
            }
            if (empty($common['image'])) {
                $issues['missing_image'][] = $p->sku;
            }
            if (empty($values['categories'] ?? [])) {
                $issues['missing_category'][] = $p->sku;
            }
        }

        $total = $products->count();
        $issueCount = array_sum(array_map('count', $issues));

        $healthScore = $total > 0
            ? round((1 - ($issueCount / ($total * 4))) * 100)
            : 100;

        $resultData = [
            'scanned'      => $total,
            'health_score' => $healthScore,
            'issues'       => array_map(fn ($skus) => [
                'count' => count($skus),
                'skus'  => array_slice($skus, 0, 20),
            ], $issues),
        ];

        // Store report as a task
        DB::table('ai_agent_tasks')->insert([
            'type'         => 'quality_report',
            'status'       => 'completed',
            'priority'     => 'normal',
            'config'       => json_encode(['channel' => $channel, 'locale' => $locale]),
            'result'       => json_encode($resultData),
            'progress'     => 100,
            'completed_at' => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Create proactive notification if issues found
        if ($issueCount > 0) {
            $topIssues = [];
            foreach ($issues as $type => $skus) {
                if (count($skus) > 0) {
                    $topIssues[] = count($skus).' '.str_replace('_', ' ', $type);
                }
            }

            DB::table('ai_agent_tasks')->insert([
                'type'       => 'notification',
                'status'     => 'pending',
                'priority'   => $healthScore < 50 ? 'high' : 'normal',
                'config'     => json_encode([
                    'title'   => "Catalog health: {$healthScore}/100",
                    'message' => 'Quality scan found: '.implode(', ', $topIssues).'. Run the AI Agent to auto-fix missing content.',
                    'action'  => 'Open AI Agent Chat to fix issues',
                ]),
                'result'     => json_encode($resultData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Output summary
        $this->info("Health Score: {$healthScore}/100");
        $this->table(
            ['Issue', 'Count'],
            collect($issues)->map(fn ($skus, $key) => [
                str_replace('_', ' ', ucfirst($key)),
                count($skus),
            ])->toArray(),
        );

        if ($issueCount > 0) {
            $this->warn("Found {$issueCount} issues across {$total} products.");
            Log::info("CatalogQualityMonitor: Health {$healthScore}/100, {$issueCount} issues across {$total} products");
        } else {
            $this->info('No quality issues found. Catalog is healthy!');
        }

        return self::SUCCESS;
    }
}
