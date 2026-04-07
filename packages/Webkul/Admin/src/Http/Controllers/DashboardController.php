<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;

class DashboardController extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    protected $typeFunctions = [
        'total-catalogs'        => 'getTotalCatalogs',
        'total-configurations'  => 'getTotalConfigurations',
        'product-stats'         => 'getProductStats',
        'recent-activity'       => 'getRecentActivity',
        'data-transfer-status'  => 'getDataTransferStatus',
        'needs-attention'       => 'getNeedsAttention',
        'channel-readiness'     => 'getChannelReadiness',
    ];

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Dashboard $dashboardHelper) {}

    /**
     * Dashboard page.
     *
     * @return View|JsonResponse
     */
    public function index(): View
    {
        return view('admin::dashboard.index')->with([

        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function stats(): JsonResponse
    {
        $type = request()->query('type');

        if (! isset($this->typeFunctions[$type])) {
            return response()->json(['message' => trans('admin::app.dashboard.invalid-type')], JsonResponse::HTTP_BAD_REQUEST);
        }

        $stats = $this->dashboardHelper->{$this->typeFunctions[$type]}();

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
