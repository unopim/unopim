<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Webkul\Admin\Helpers\CustomCss;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AppearanceForm;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\CoreConfigRepository;

class AppearanceController extends Controller
{
    /**
     * Create a controller instance.
     */
    public function __construct(
        protected FileStorer $fileStorer,
        protected CoreConfigRepository $coreConfigRepository,
        protected CustomCss $customCss
    ) {}

    /**
     * Display appearance settings page.
     */
    public function index(): View
    {
        return view('admin::settings.appearance.index');
    }

    /**
     * Update logo, favicon and custom CSS.
     */
    public function update(AppearanceForm $request): RedirectResponse
    {
        $this->handleUpload($request, 'logo_image', 'general.design.admin_logo.logo_image');

        $this->handleUpload($request, 'favicon', 'general.design.admin_logo.favicon');

        $this->handleCustomCss($request);

        $this->flushConfigCache();

        return redirect()
            ->route('admin.settings.system.index')
            ->with('success', trans('admin::app.settings.appearance.update-success'));
    }

    /**
     * Persist a field submitted by the media.image component.
     *
     * A new file replaces the stored value; an unchanged image is resubmitted
     * as its string path and left untouched; an absent field means the user
     * removed the image, so the config is cleared to fall back to the default.
     */
    private function handleUpload(AppearanceForm $request, string $field, string $code): void
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            $file = is_array($file) ? current($file) : $file;
            $extension = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());

            $path = $this->fileStorer->storeAs(
                path: 'configuration',
                name: Str::random(40).'.'.$extension,
                file: $file,
                options: [FileStorer::HASHED_FOLDER_NAME_KEY => true],
            );

            $this->replaceConfigFile($code, $path);

            return;
        }

        if (! empty($request->input($field))) {
            return;
        }

        $this->clearConfigFile($code);
    }

    /**
     * Persist the sanitized custom CSS, or clear it when the field is emptied.
     */
    private function handleCustomCss(AppearanceForm $request): void
    {
        if (! $request->has('custom_css')) {
            return;
        }

        $css = $this->customCss->sanitize($request->input('custom_css'));

        if ($css === '') {
            CoreConfig::query()
                ->where('code', CustomCss::CONFIG_CODE)
                ->whereNull('channel_code')
                ->whereNull('locale_code')
                ->delete();

            return;
        }

        CoreConfig::query()->updateOrCreate(
            [
                'code'         => CustomCss::CONFIG_CODE,
                'channel_code' => null,
                'locale_code'  => null,
            ],
            ['value' => $css]
        );
    }

    /**
     * Invalidate the cached core config reads.
     *
     * getConfigData() reads through the cached CoreConfigRepository. Because the
     * uploads above are persisted with raw Eloquent instead of the repository,
     * Prettus' cache-clean listener never fires, so the logo/favicon would keep
     * serving the previous (stale) value until the cache expired. Dispatching the
     * repository event forgets only the CoreConfig cache keys.
     */
    private function flushConfigCache(): void
    {
        event(new RepositoryEntityUpdated(
            $this->coreConfigRepository,
            $this->coreConfigRepository->getModel()
        ));
    }

    /**
     * Remove a stored config file and its value so the UnoPim default renders.
     */
    private function clearConfigFile(string $code): void
    {
        $existing = CoreConfig::query()
            ->where('code', $code)
            ->whereNull('channel_code')
            ->whereNull('locale_code')
            ->first();

        if (! $existing) {
            return;
        }

        if ($existing->value && Storage::exists($existing->value)) {
            Storage::delete($existing->value);
        }

        $existing->delete();
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
