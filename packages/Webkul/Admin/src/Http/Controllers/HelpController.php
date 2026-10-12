<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\VersionCheck;
use Webkul\Admin\Http\Requests\PromoDismissRequest;
use Webkul\User\Repositories\AdminPromoDismissalRepository;

class HelpController extends Controller
{
    /**
     * Create a controller instance.
     */
    public function __construct(protected VersionCheck $versionCheck) {}

    /**
     * Display the help & resources page.
     */
    public function index(): View
    {
        return view('admin::help.index');
    }

    /**
     * Persist a promo banner dismissal for the current admin.
     */
    public function dismissPromo(PromoDismissRequest $request): JsonResponse
    {
        $banner = $request->input('banner');

        // Derive the version server-side (mirrors PromoBanner) instead of trusting the client.
        $version = $banner === 'upgrade'
            ? (string) $this->versionCheck->latestVersion()
            : '';

        app(AdminPromoDismissalRepository::class)->dismiss(
            auth()->guard('admin')->id(),
            $banner,
            $version,
        );

        return new JsonResponse(['message' => trans('admin::app.help.banners.dismissed')]);
    }
}
