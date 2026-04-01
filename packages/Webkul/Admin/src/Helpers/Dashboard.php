<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Admin\Helpers\Reporting\Attribute;
use Webkul\Admin\Helpers\Reporting\AttributeFamily;
use Webkul\Admin\Helpers\Reporting\AttributeGroup;
use Webkul\Admin\Helpers\Reporting\Category;
use Webkul\Admin\Helpers\Reporting\Channel;
use Webkul\Admin\Helpers\Reporting\Currency;
use Webkul\Admin\Helpers\Reporting\Locale;
use Webkul\Admin\Helpers\Reporting\Product;

class Dashboard
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Product $productReporting,
        protected AttributeFamily $attributeFamily,
        protected Attribute $attribute,
        protected AttributeGroup $attributeGroup,
        protected Category $category,
        protected Locale $locale,
        protected Channel $channel,
        protected Currency $currency
    ) {}

    /**
     * This method calculates and returns the total number of various catalog entities.
     *
     * @return array An associative array containing the total count of each catalog entity.
     */
    public function getTotalCatalogs()
    {
        return Cache::remember('dashboard.total_catalogs', self::CACHE_TTL, function () {
            return [
                'totalCategories' => $this->category->getTotalCategories(),
                'totalProducts'   => $this->productReporting->getTotalProducts(),
            ];
        });
    }

    /**
     * This method calculates and returns the total number of various configuration entities.
     *
     * @return array An associative array containing the total count of each configuration entity.
     */
    public function getTotalConfigurations()
    {
        return Cache::remember('dashboard.total_configurations', self::CACHE_TTL, function () {
            return [
                'totalCurrencies'        => $this->currency->getTotalActiveCurrencies(),
                'totalChannels'          => $this->channel->getTotalChannels(),
                'totalLocales'           => $this->locale->getTotalActiveLocales(),
                'totalAttributes'        => $this->attribute->getTotalAttributes(),
                'totalAttributeGroups'   => $this->attributeGroup->getTotalAttributeGroups(),
                'totalAttributeFamilies' => $this->attributeFamily->getTotalFamilies(),
            ];
        });
    }

    /**
     * Get product statistics: type distribution, status breakdown, trends, and insights.
     */
    public function getProductStats()
    {
        return Cache::remember('dashboard.product_stats', self::CACHE_TTL, function () {
            // Single query for type + status using conditional aggregation
            $stats = DB::table('products')
                ->select(
                    'type',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_count'),
                    DB::raw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_count')
                )
                ->groupBy('type')
                ->get();

            $typeDistribution = [];
            $totalActive = 0;
            $totalInactive = 0;

            foreach ($stats as $row) {
                $typeDistribution[$row->type] = (int) $row->total;
                $totalActive += (int) $row->active_count;
                $totalInactive += (int) $row->inactive_count;
            }

            $startDate = now()->subDays(6)->startOfDay();

            $updateTrends = DB::table('products')
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('updated_at', '>=', $startDate)
                ->whereColumn('updated_at', '!=', 'created_at')
                ->groupBy(DB::raw('DATE(updated_at)'))
                ->get()
                ->pluck('count', 'date');

            $creationRaw = DB::table('products')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->pluck('count', 'date');

            // Fill missing days with 0
            $created = [];
            $updated = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $created[$date] = (int) ($creationRaw[$date] ?? 0);
                $updated[$date] = (int) ($updateTrends[$date] ?? 0);
            }

            // Quick insights
            $newThisWeek = DB::table('products')
                ->where('created_at', '>=', $startDate)
                ->count();

            $withVariants = DB::table('products')
                ->where('type', 'configurable')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('product_relations')
                        ->whereColumn('product_relations.parent_id', 'products.id');
                })
                ->count();

            $avgCompleteness = null;

            if (Schema::hasColumn('products', 'avg_completeness_score')) {
                $avgCompleteness = DB::table('products')
                    ->whereNotNull('avg_completeness_score')
                    ->avg('avg_completeness_score');
            }

            // Enrichment velocity
            $enrichedThisWeek = 0;
            $enrichedLastWeek = 0;

            if (Schema::hasTable('product_completeness')) {
                $enrichedThisWeek = DB::table('product_completeness')
                    ->where('updated_at', '>=', now()->subDays(7))
                    ->distinct()
                    ->count('product_id');

                $enrichedLastWeek = DB::table('product_completeness')
                    ->whereBetween('updated_at', [now()->subDays(14), now()->subDays(7)])
                    ->distinct()
                    ->count('product_id');
            }

            return [
                'typeDistribution'  => $typeDistribution,
                'statusBreakdown'   => [
                    'active'   => $totalActive,
                    'inactive' => $totalInactive,
                ],
                'creationTrend'     => $created,
                'updateTrend'       => $updated,
                'totalProducts'     => $totalActive + $totalInactive,
                'newThisWeek'       => $newThisWeek,
                'withVariants'      => $withVariants,
                'avgCompleteness'   => $avgCompleteness !== null ? round($avgCompleteness) : null,
                'enrichedThisWeek'  => $enrichedThisWeek,
                'enrichedLastWeek'  => $enrichedLastWeek,
            ];
        });
    }

    /**
     * Get items that need attention.
     */
    public function getNeedsAttention()
    {
        return Cache::remember('dashboard.needs_attention', self::CACHE_TTL, function () {
            $unenriched = 0;
            $lowCompleteness = 0;

            if (Schema::hasColumn('products', 'avg_completeness_score')) {
                $unenriched = DB::table('products')
                    ->where(function ($q) {
                        $q->whereNull('avg_completeness_score')
                            ->orWhere('avg_completeness_score', 0);
                    })
                    ->count();

                $lowCompleteness = DB::table('products')
                    ->where('avg_completeness_score', '>', 0)
                    ->where('avg_completeness_score', '<', 50)
                    ->count();
            }

            $failedJobs = DB::table('job_track')
                ->where('state', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            return [
                'unenriched'      => $unenriched,
                'failedJobs'      => $failedJobs,
                'lowCompleteness' => $lowCompleteness,
            ];
        });
    }

    /**
     * Get channel readiness data.
     */
    public function getChannelReadiness()
    {
        return Cache::remember('dashboard.channel_readiness', self::CACHE_TTL, function () {
            if (! Schema::hasTable('product_completeness')) {
                return [];
            }

            $completenessTable = DB::getTablePrefix().'product_completeness';

            $readiness = DB::table('product_completeness')
                ->join('channels', 'channels.id', '=', 'product_completeness.channel_id')
                ->select(
                    'channels.code as channel',
                    DB::raw("COUNT(DISTINCT {$completenessTable}.product_id) as total"),
                    DB::raw("COUNT(DISTINCT CASE WHEN {$completenessTable}.score >= 80 THEN {$completenessTable}.product_id END) as ready")
                )
                ->groupBy('channels.code')
                ->get();

            return $readiness->map(fn ($row) => [
                'channel'    => $row->channel,
                'total'      => (int) $row->total,
                'ready'      => (int) $row->ready,
                'percentage' => $row->total > 0 ? round(($row->ready / $row->total) * 100) : 0,
            ])->toArray();
        });
    }

    /**
     * Get recent activity from audits table.
     */
    public function getRecentActivity()
    {
        $activities = DB::table('audits')
            ->leftJoin('admins', 'admins.id', '=', 'audits.user_id')
            ->select(
                'audits.id',
                'audits.tags as entity_type',
                'audits.auditable_type',
                'audits.event',
                'admins.name as user_name',
                'audits.updated_at',
                'audits.history_id',
            )
            ->orderByDesc('audits.updated_at')
            ->groupBy('audits.updated_at', 'audits.user_id', 'audits.version_id', 'admins.name', 'audits.id', 'audits.tags', 'audits.auditable_type', 'audits.event', 'audits.history_id')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                $activity->time_ago = $this->calculateTimeAgo($activity->updated_at);
                $activity->entity_type = $this->resolveEntityType($activity->entity_type, $activity->auditable_type);
                unset($activity->auditable_type);

                return $activity;
            });

        return [
            'activities' => $activities,
        ];
    }

    /**
     * Get recent data transfer job statuses.
     */
    public function getDataTransferStatus()
    {
        $recentJobs = DB::table('job_track')
            ->leftJoin('admins', 'admins.id', '=', 'job_track.user_id')
            ->select(
                'job_track.id',
                'job_track.state',
                'job_track.type',
                'job_track.processed_rows_count',
                'job_track.invalid_rows_count',
                'job_track.errors_count',
                'job_track.started_at',
                'job_track.completed_at',
                'job_track.created_at',
                'admins.name as user_name',
            )
            ->orderByDesc('job_track.created_at')
            ->limit(5)
            ->get()
            ->map(function ($job) {
                $job->time_ago = $this->calculateTimeAgo($job->created_at);

                return $job;
            });

        $jobSummary = DB::table('job_track')
            ->select('state', DB::raw('COUNT(*) as count'))
            ->groupBy('state')
            ->get()
            ->pluck('count', 'state')
            ->toArray();

        return [
            'recentJobs' => $recentJobs,
            'jobSummary' => $jobSummary,
        ];
    }

    /**
     * Resolve the entity type from audit tags or fallback to auditable_type.
     */
    protected function resolveEntityType(?string $tags, ?string $auditableType): ?string
    {
        if (! empty($tags)) {
            return $tags;
        }

        if (empty($auditableType)) {
            return null;
        }

        $classBasename = class_basename($auditableType);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $classBasename));
    }

    /**
     * Calculate human-readable time ago string.
     */
    protected function calculateTimeAgo($dateTime)
    {
        $time = strtotime($dateTime);
        $current = time();
        $diff = $current - $time;

        $second = 1;
        $minute = 60;
        $hour = 60 * 60;
        $day = 24 * 60 * 60;
        $month = 30 * 24 * 60 * 60;
        $year = 365 * 24 * 60 * 60;

        if ($diff < $minute) {
            return trans('admin::app.dashboard.index.just-now');
        } elseif ($diff < $hour) {
            $ago = round($diff / $minute);

            return $ago.' '.($ago > 1 ? 'minutes' : 'minute').' '.trans('admin::app.dashboard.index.ago');
        } elseif ($diff < $day) {
            $ago = round($diff / $hour);

            return $ago.' '.($ago > 1 ? 'hours' : 'hour').' '.trans('admin::app.dashboard.index.ago');
        } elseif ($diff < $month) {
            $ago = round($diff / $day);

            return $ago.' '.($ago > 1 ? 'days' : 'day').' '.trans('admin::app.dashboard.index.ago');
        } elseif ($diff < $year) {
            $ago = round($diff / $month);

            return $ago.' '.($ago > 1 ? 'months' : 'month').' '.trans('admin::app.dashboard.index.ago');
        } else {
            $ago = round($diff / $year);

            return $ago.' '.($ago > 1 ? 'years' : 'year').' '.trans('admin::app.dashboard.index.ago');
        }
    }
}
