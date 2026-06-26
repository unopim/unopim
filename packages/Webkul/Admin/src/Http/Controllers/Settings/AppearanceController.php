<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Models\CoreConfig;

class AppearanceController extends Controller
{
    /**
     * Display appearance settings page.
     */
    public function index(): View
    {
        return view('admin::settings.appearance.index');
    }

    /**
     * Update logo and favicon.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo_image' => ['nullable', 'image', 'max:2048'],
            'favicon'    => ['nullable', 'file', 'mimes:ico,png,svg,webp', 'max:1024'],
        ]);

        if ($request->hasFile('logo_image')) {
            $this->replaceConfigFile('general.design.admin_logo.logo_image', $request->file('logo_image')->store('configuration'));
        }

        if ($request->hasFile('favicon')) {
            $this->replaceConfigFile('general.design.admin_logo.favicon', $request->file('favicon')->store('configuration'));
        }

        return redirect()
            ->route('admin.settings.appearance.index')
            ->with('success', trans('admin::app.settings.appearance.update-success'));
    }

    /**
     * Replace a core config file value and cleanup previous file.
     */
    private function replaceConfigFile(string $code, string $newPath): void
    {
        $existing = CoreConfig::query()
            ->where('code', $code)
            ->whereNull('channel_code')
            ->whereNull('locale_code')
            ->first();

        if ($existing?->value && $existing->value !== $newPath && Storage::exists($existing->value)) {
            Storage::delete($existing->value);
        }

        CoreConfig::query()->updateOrCreate(
            [
                'code'         => $code,
                'channel_code' => null,
                'locale_code'  => null,
            ],
            ['value' => $newPath]
        );
    }
}
