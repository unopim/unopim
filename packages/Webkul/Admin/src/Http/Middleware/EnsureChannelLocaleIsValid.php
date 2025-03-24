<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to validate that the requested locale is available for the current requested channel.
 * If the locale is not available, it redirects to the first available locale for that channel.
 */
class EnsureChannelLocaleIsValid {

    /**
     * Handle the incoming request.
     *
     * This middleware checks if the locale in the request is valid for the current channel.
     * If not, it redirects to the first available locale for that channel while preserving
     * other route parameters and query string.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance
     * @param  \Closure  $next  The next middleware/handler in the pipeline
     * @return mixed  Response or redirect
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Webkul\Core\Models\Channel $requestedChannel */
        $requestedChannel = core()->getRequestedChannel();
        $requestedLocaleCode = core()->getRequestedLocaleCode();
        $route = $request->route();

        // Check that the locale is available in the current channel
        if($requestedChannel->locales()->where('code', $requestedLocaleCode)->first() === null) {
            // Get all route parameters
            $parameters = $route->parameters();

            // Update the locale parameter to use the first available locale for this channel
            $parameters['locale'] = $requestedChannel->locales()->first()->code;

            // Ensure channel parameter is set
            $parameters['channel'] = $requestedChannel->code;

            // Return redirect with route parameters and preserving query parameters
            $routeName = $route->getName();

            // If route has name, redirect with parameters
            if($routeName !== null) {
                return redirect()->route($routeName, $parameters);
            } else if($route->getActionName() !== null) {
                // For routes without names, use the current URL but update query parameters
                return redirect()->action($route->getActionName(), $parameters);
            } else {
                // As a last resort, return user back to the previous page
                return redirect()->back();
            }
        }

        // If the locale is valid for this channel, continue with the request
        return $next($request);
    }

}