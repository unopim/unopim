<?php

use Webkul\Theme\Themes;
use Webkul\Theme\ViewRenderEventManager;

if (! function_exists('themes')) {
    /**
     * Themes.
     *
     * @return Themes
     */
    function themes()
    {
        return app()->make('themes');
    }
}

if (! function_exists('unopim_asset')) {
    /**
     * unopim asset.
     *
     * @return string
     */
    function unopim_asset(string $path, ?string $namespace = null)
    {
        return themes()->url($path, $namespace);
    }
}

if (! function_exists('view_render_event')) {
    /**
     * View render event.
     *
     * @return mixed
     */
    function view_render_event(string $eventName, mixed $params = null)
    {
        app()->singleton(ViewRenderEventManager::class);

        $viewEventManager = app()->make(ViewRenderEventManager::class);

        $viewEventManager->handleRenderEvent($eventName, $params);

        return $viewEventManager->render();
    }
}
