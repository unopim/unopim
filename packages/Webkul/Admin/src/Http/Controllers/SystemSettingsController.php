<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

        if (! $entry || ! ($group = $this->systemSettings->formGroup($entry))) {
            return redirect()->route('admin.settings.system.index');
        }

        $this->enforceSectionAccess($entry);

        return view('admin::system.settings-edit', [
            'entry' => $entry,
            'group' => $group,
        ]);
    }

    /**
     * Persist a `fields` row to DB core-config (never `.env`).
     */
    public function update(Request $request, string $key): RedirectResponse
    {
        $entry = $this->systemSettings->find($key);

        if (! $entry || ! ($group = $this->systemSettings->formGroup($entry))) {
            return redirect()->route('admin.settings.system.index');
        }

        $this->enforceSectionAccess($entry);

        $this->coreConfigRepository->create($this->allowedConfig($request, $group));

        session()->flash('success', trans('admin::app.settings.system-settings.save-message'));

        return redirect()->route('admin.settings.system.edit', $key);
    }

    /**
     * Deny direct access to a section the admin's role is not granted. Every row
     * shares one generic editor route, so the Bouncer middleware can only gate at
     * the umbrella `configuration.system_settings` level — per-section access is
     * enforced here against the hub row's own `acl`.
     *
     * @param  array<string, mixed>  $entry
     */
    protected function enforceSectionAccess(array $entry): void
    {
        abort_unless(
            empty($entry['acl']) || bouncer()->hasPermission((string) $entry['acl']),
            403,
            trans('admin::app.errors.403.message')
        );
    }

    /**
     * Restrict the posted payload to the config codes this group actually declares.
     * Without this, `create()` would persist any code posted (unknown codes are
     * stored verbatim), letting a crafted request write arbitrary core-config
     * outside the edited group — e.g. SMTP creds, AI keys or the debug allow-list.
     *
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    protected function allowedConfig(Request $request, array $group): array
    {
        $allowed = collect((array) ($group['fields'] ?? []))
            ->pluck('name')
            ->filter()
            ->map(fn ($name): string => ((string) $group['key']).'.'.((string) $name))
            ->all();

        /** @var array<string, mixed> $payload */
        $payload = [];

        foreach (Arr::dot($request->except(['_token', 'admin_locale'])) as $code => $value) {
            $matches = collect($allowed)->contains(
                fn ($allowedCode) => $code === $allowedCode || str_starts_with($code, $allowedCode.'.')
            );

            if ($matches) {
                Arr::set($payload, $code, $value);
            }
        }

        // Preserve the channel/locale scope keys the repository understands.
        foreach (['locale', 'channel'] as $scope) {
            if ($request->filled($scope)) {
                $payload[$scope] = $request->input($scope);
            }
        }

        return $payload;
    }
}
