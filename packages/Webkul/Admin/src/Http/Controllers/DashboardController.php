<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Core\Platform\Module;
use Webkul\Core\Platform\ModuleGate;

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

        if (isset($this->dashboardHelper::PIM_WIDGETS[$type]) && ! app(ModuleGate::class)->enabled(Module::Pim)) {
            return response()->json(['message' => trans('admin::app.dashboard.invalid-type')], JsonResponse::HTTP_NOT_FOUND);
        }

        $stats = $this->dashboardHelper->{$this->typeFunctions[$type]}();

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
