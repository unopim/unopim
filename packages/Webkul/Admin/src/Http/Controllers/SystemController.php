<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\View\View;
use Webkul\Admin\Helpers\SystemInformation;

class SystemController extends Controller
{
    /**
     * Create a controller instance.
     */
    public function __construct(protected SystemInformation $systemInformation) {}

    /**
     * Display the system information page.
     */
    public function information(): View
    {
        return view('admin::help.system-info', [
            'sections'   => $this->systemInformation->all(),
            'extensions' => $this->systemInformation->extensions(),
            'packages'   => $this->systemInformation->packages(),
        ]);
    }

    /**
     * Display the combined system settings page (Appearance, SMTP and Debug).
     */
    public function settings(): View
    {
        $config = collect(config('core'));

        return view('admin::system.settings', [
            'smtpGroup'  => $config->firstWhere('key', 'emails.configure.email_settings'),
            'debugGroup' => $config->firstWhere('key', 'general.debug.settings'),
        ]);
    }
}
