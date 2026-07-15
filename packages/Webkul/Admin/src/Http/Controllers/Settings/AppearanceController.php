<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Webkul\Admin\Http\Controllers\Controller;
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
        protected CoreConfigRepository $coreConfigRepository
    ) {}

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
            'logo_image'   => ['nullable'],
            'logo_image.*' => ['nullable', 'image', 'mimes:bmp,jpeg,jpg,png,webp,svg', 'max:2048'],
            'favicon'      => ['nullable'],
            'favicon.*'    => ['nullable', 'file', 'mimes:ico,png,svg,webp', 'max:1024'],
        ]);

        $this->handleUpload($request, 'logo_image', 'general.design.admin_logo.logo_image');

        $this->handleUpload($request, 'favicon', 'general.design.admin_logo.favicon');

        $this->flushConfigCache();

        return redirect()
            ->route('admin.settings.system.index')
            ->with('success', trans('admin::app.settings.appearance.update-success'));
    }

    /**
     * Persist a field submitted by the media.images component.
     *
     * A new file replaces the stored value; an unchanged image is resubmitted
     * as its string path and left untouched; an absent field means the user
     * removed the image, so the config is cleared to fall back to the default.
     */
    private function handleUpload(Request $request, string $field, string $code): void
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);

            $path = $this->fileStorer->store(
                path: 'configuration',
                file: is_array($file) ? current($file) : $file,
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
