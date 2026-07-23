<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Vite;
use Webkul\Theme\Exceptions\ViterNotFound;

class Themes
{
    /**
     * Contains current activated theme code.
     *
     * @var string
     */
    protected $activeTheme;

    /**
     * Contains all themes.
     *
     * @var array
     */
    protected $themes = [];

    /**
     * Contains laravel default view paths.
     *
     * @var array
     */
    protected $laravelViewsPath;

    /**
     * Contains default theme code.
     *
     * @var string
     */
    protected $defaultThemeCode = 'default';

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
     *
     * @return array
     */
    public function all()
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
     *
     * @return Theme
     */
    public function set(string $themeName)
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

        $themeViewFinder = resolve('view.finder');

        $themeViewFinder->setPaths($paths);

        return $theme;
    }

    /**
     * Get current theme.
     *
     * @return Theme
     */
    public function current()
    {
        return $this->activeTheme ?? $this->set($this->defaultThemeCode);
    }

    /**
     * Get current theme's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->current()?->name ?? '';
    }

    /**
     * Find a theme by it's name.
     *
     * @return Theme
     */
    public function find(string $themeName)
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
     *
     * @return array
     */
    public function getLaravelViewPaths()
    {
        return $this->laravelViewsPath;
    }

    /**
     * Return the asset URL of the current theme if a theme is found; otherwise, check from the namespace.
     *
     * @return string
     */
    public function url(string $filename, ?string $namespace = null)
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

        throw_if(empty($viters[$namespace]), ViterNotFound::class, $namespace);

        $viteUrl = trim($viters[$namespace]['package_assets_directory'], '/').'/'.$url;

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->asset($viteUrl);
    }

    /**
     * Set UnoPim vite in current theme.
     *
     * @param  mixed  $entryPoints
     * @return mixed
     */
    public function setUnoPimVite($entryPoints, ?string $namespace = null)
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

        throw_if(empty($viters[$namespace]), ViterNotFound::class, $namespace);

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->withEntryPoints($entryPoints);
    }
}
