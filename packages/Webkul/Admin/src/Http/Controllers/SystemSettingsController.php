<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\SystemSettings;
use Webkul\Core\Repositories\CoreConfigRepository;

class SystemSettingsController extends Controller
{
    public function __construct(
        protected SystemSettings $systemSettings,
        protected CoreConfigRepository $coreConfigRepository
    ) {}

    /**
     * The settings hub — grouped section cards, each with rows that link to a
     * dedicated page or to the generic fields editor.
     */
    public function index(): View
    {
        return view('admin::system.settings-hub', [
            'tree' => $this->systemSettings->tree(),
        ]);
    }

    /**
     * Generic edit page for a `fields` row.
     */
    public function edit(string $key): View|RedirectResponse
    {
        $entry = $this->systemSettings->find($key);

        if (! $entry || empty($entry['fields'])) {
            return redirect()->route('admin.settings.system.index');
        }

        return view('admin::system.settings-edit', ['entry' => $entry]);
    }

    /**
     * Persist a `fields` row to DB core-config (never `.env`).
     */
    public function update(Request $request, string $key): RedirectResponse
    {
        $entry = $this->systemSettings->find($key);

        if (! $entry || empty($entry['fields'])) {
            return redirect()->route('admin.settings.system.index');
        }

        $this->coreConfigRepository->create($request->except(['_token', 'admin_locale']));

        session()->flash('success', trans('admin::app.settings.system-settings.save-message'));

        return redirect()->route('admin.settings.system.index');
    }
}
