<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class CatalogSummary implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('catalog_summary')
            ->for('Get catalog statistics: product counts, categories, attributes, imports/exports, users.')
            ->using(function () use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'dashboard')) {
                    return $denied;
                }

                $totalProducts = DB::table('products')->count();
                $activeProducts = DB::table('products')->where('status', 1)->count();
                $inactiveProducts = $totalProducts - $activeProducts;

                $productTypes = DB::table('products')
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray();

                $totalCategories = DB::table('categories')->count();
                $totalAttributes = DB::table('attributes')->count();
                $totalFamilies = DB::table('attribute_families')->count();

                $totalChannels = DB::table('channels')->count();
                $totalLocales = DB::table('locales')->where('status', 1)->count();
                $totalCurrencies = DB::table('currencies')->where('status', 1)->count();
                $totalUsers = DB::table('admins')->count();
                $totalRoles = DB::table('roles')->count();

                // Recent imports/exports
                $recentJobs = DB::table('job_track')
                    ->select('id', 'state', 'type', 'created_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->toArray();

                // Products created in last 7 days
                $recentProducts = DB::table('products')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();

                return json_encode([
                    'products' => [
                        'total'               => $totalProducts,
                        'active'              => $activeProducts,
                        'inactive'            => $inactiveProducts,
                        'by_type'             => $productTypes,
                        'created_last_7_days' => $recentProducts,
                    ],
                    'catalog' => [
                        'categories' => $totalCategories,
                        'attributes' => $totalAttributes,
                        'families'   => $totalFamilies,
                    ],
                    'system' => [
                        'channels'   => $totalChannels,
                        'locales'    => $totalLocales,
                        'currencies' => $totalCurrencies,
                        'users'      => $totalUsers,
                        'roles'      => $totalRoles,
                    ],
                    'recent_jobs' => $recentJobs,
                ]);
            });
    }
}
