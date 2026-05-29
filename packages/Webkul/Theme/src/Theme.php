<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Vite;

class Theme
{
    /**
     * Contains theme parent.
     */
    public ?Theme $parent = null;

    /**
     * Create a new theme instance.
     */
    public function __construct(
        public string $code,
        public ?string $name = null,
        public ?string $assetsPath = null,
        public ?string $viewsPath = null,
        public array $vite = []
    ) {
        $this->assetsPath = $assetsPath ?? $code;

        $this->viewsPath = $viewsPath ?? $code;
    }

    /**
     * Sets the parent.
     *
     * @param  Theme
     */
    public function setParent(Theme $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Return the parent.
     */
    public function getParent(): ?Theme
    {
        return $this->parent;
    }

    /**
     * Return all the possible view paths.
     */
    public function getViewPaths(): array
    {
        $paths = [];

        $theme = $this;

        do {
            if (substr((string) $theme->viewsPath, 0, 1) === DIRECTORY_SEPARATOR) {
                $path = base_path(substr((string) $theme->viewsPath, 1));
            } else {
                $path = $theme->viewsPath;
            }

            if (! in_array($path, $paths)) {
                $paths[] = $path;
            }
        } while ($theme = $theme->parent);

        return $paths;
    }

    /**
     * Convert to asset url based on current theme.
     */
    public function url(string $url): string
    {
        $viteUrl = trim((string) $this->vite['package_assets_directory'], '/').'/'.$url;

        return Vite::useHotFile($this->vite['hot_file'])
            ->useBuildDirectory($this->vite['build_directory'])
            ->asset($viteUrl);
    }

    /**
     * Set UnoPim vite.
     */
    public function setUnoPimVite(array $entryPoints): \Illuminate\Foundation\Vite
    {
        return Vite::useHotFile($this->vite['hot_file'])
            ->useBuildDirectory($this->vite['build_directory'])
            ->withEntryPoints($entryPoints);
    }
}
