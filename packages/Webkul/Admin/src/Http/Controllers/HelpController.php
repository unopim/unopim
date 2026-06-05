<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Requests\PromoDismissRequest;
use Webkul\User\Repositories\AdminPromoDismissalRepository;

class HelpController extends Controller
{
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
        app(AdminPromoDismissalRepository::class)->dismiss(
            auth()->guard('admin')->id(),
            $request->input('banner'),
            (string) $request->input('version', ''),
        );

        return new JsonResponse(['message' => trans('admin::app.help.banners.dismissed')]);
    }
}
