<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Vite;
use Webkul\Theme\Exceptions\ViterNotFound;

class Themes
{
    /**
     * Contains current activated theme code.
     */
    protected ?Theme $activeTheme = null;

    /**
     * Contains all themes.
     */
    protected array $themes = [];

    /**
     * Contains laravel default view paths.
     */
    protected array $laravelViewsPath;

    /**
     * Contains default theme code.
     */
    protected string $defaultThemeCode = 'default';

    /**
     * Create a new themes instance.
     */
    public function __construct()
    {
        $this->defaultThemeCode = Config::get('themes.admin-default');

        $this->laravelViewsPath = Config::get('view.paths');

        $this->loadThemes();
    }

    /**
     * Return list of all registered themes.
     */
    public function all(): array
    {
        return $this->themes;
    }

    /**
     * Return list of registered themes.
     */
    public function getChannelThemes(): array
    {
        $themes = config('themes.admin', []);

        $channelThemes = [];

        foreach ($themes as $code => $data) {
            $channelThemes[] = new Theme(
                $code,
                $data['name'] ?? '',
                $data['assets_path'] ?? '',
                $data['views_path'] ?? '',
                $data['vite'] ?? [],
            );

            if (! empty($data['parent'])) {
                $parentThemes[$code] = $data['parent'];
            }
        }

        return $channelThemes;
    }

    /**
     * Check if specified exists.
     */
    public function exists(string $themeName): bool
    {
        foreach ($this->themes as $theme) {
            if ($theme->code == $themeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare all themes.
     */
    public function loadThemes(): void
    {
        $parentThemes = [];

        $themes = config('themes.admin', []);

        foreach ($themes as $code => $data) {
            $this->themes[] = new Theme(
                $code,
                $data['name'] ?? '',
                $data['assets_path'] ?? '',
                $data['views_path'] ?? '',
                $data['vite'] ?? [],
            );

            if (! empty($data['parent'])) {
                $parentThemes[$code] = $data['parent'];
            }
        }

        foreach ($parentThemes as $childCode => $parentCode) {
            $child = $this->find($childCode);

            $parent = $this->exists($parentCode) ? $this->find($parentCode) : new Theme($parentCode);

            $child->setParent($parent);
        }
    }

    /**
     * Enable theme.
     */
    public function set(string $themeName): Theme
    {
        $theme = $this->exists($themeName) ? $this->find($themeName) : new Theme($themeName);

        $this->activeTheme = $theme;

        $paths = $theme->getViewPaths();

        foreach ($this->laravelViewsPath as $path) {
            if (! in_array($path, $paths)) {
                $paths[] = $path;
            }
        }

        Config::set('view.paths', $paths);

        $themeViewFinder = app('view.finder');

        $themeViewFinder->setPaths($paths);

        return $theme;
    }

    /**
     * Get current theme.
     */
    public function current(): Theme
    {
        return $this->activeTheme ?? $this->set($this->defaultThemeCode);
    }

    /**
     * Get current theme's name.
     */
    public function getName(): string
    {
        return $this->current()?->name ?? '';
    }

    /**
     * Find a theme by it's name.
     */
    public function find(string $themeName): Theme
    {
        foreach ($this->themes as $theme) {
            if ($theme->code == $themeName) {
                return $theme;
            }
        }

        throw new Exceptions\ThemeNotFound($themeName);
    }

    /**
     * Original view paths defined in `config.view.php`.
     */
    public function getLaravelViewPaths(): array
    {
        return $this->laravelViewsPath;
    }

    /**
     * Return the asset URL of the current theme if a theme is found; otherwise, check from the namespace.
     */
    public function url(string $filename, ?string $namespace = null): string
    {
        $url = trim($filename, '/');

        /**
         * If the namespace is null, it means the theming system is activated. We use the request URI to
         * detect the theme and provide Vite assets based on the current theme.
         */
        if (in_array($namespace, [null, '', '0'], true)) {
            return $this->current()->url($url);
        }

        /**
         * If a namespace is provided, it means the developer knows what they are doing and must create the
         * registry in the provided configuration. We will analyze based on that.
         */
        $viters = config('unopim-vite.viters');

        if (empty($viters[$namespace])) {
            throw new ViterNotFound($namespace);
        }

        $viteUrl = trim((string) $viters[$namespace]['package_assets_directory'], '/').'/'.$url;

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->asset($viteUrl);
    }

    /**
     * Set UnoPim vite in current theme.
     */
    public function setUnoPimVite(mixed $entryPoints, ?string $namespace = null): mixed
    {
        /**
         * If the namespace is null, it means the theming system is activated. We use the request URI to
         * detect the theme and provide Vite assets based on the current theme.
         */
        if (in_array($namespace, [null, '', '0'], true)) {
            return $this->current()->setUnoPimVite($entryPoints);
        }

        /**
         * If a namespace is provided, it means the developer knows what they are doing and must create the
         * registry in the provided configuration. We will analyze based on that.
         */
        $viters = config('unopim-vite.viters');

        if (empty($viters[$namespace])) {
            throw new ViterNotFound($namespace);
        }

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->withEntryPoints($entryPoints);
    }
}
