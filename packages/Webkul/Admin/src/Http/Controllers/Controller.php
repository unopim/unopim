<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Webkul\Admin\Traits\ProvidesResourceResponses;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ProvidesResourceResponses, ValidatesRequests;

    /**
     * Display a listing of the resource.
     */
    public function redirectToLogin(): RedirectResponse
    {
        return redirect()->route('admin.session.create');
    }
}
