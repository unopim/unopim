<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to validate that the requested locale is available for the current requested channel.
 * If the locale is not available, it redirects to the first available locale for that channel.
 */
class EnsureChannelLocaleIsValid
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedChannel = core()->getRequestedChannel();
        $requestedLocaleCode = core()->getRequestedLocaleCode();
        $route = $request->route();

        if ($requestedChannel?->locales()?->where('code', $requestedLocaleCode)->first() === null) {
            $parameters = $route->parameters();

            $requestedChannel ??= core()->getDefaultChannel();

            $parameters['channel'] = $requestedChannel->code;
            $parameters['locale'] = $requestedChannel->locales()->first()->code;

            $routeName = $route->getName();

            if ($routeName !== null) {
                return redirect()->route($routeName, $parameters);
            }

            $actionName = $route->getActionName();

            if ($actionName !== null) {
                return redirect()->action($actionName, $parameters);
            }

            return redirect()->back();
        }

        return $next($request);
    }
}
