<?php

namespace Webkul\AdminApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Webkul\AdminApi\Traits\ApiResponse;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleMiddleware
{
    use ApiResponse;

    /**
     * Create a middleware instance.
     *
     * @return void
     */
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedLocales = $this->getLocales($request);
        if ($requestedLocales) {
            $activeLocales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
            $localeNotExists = array_diff($requestedLocales, $activeLocales);
            if (count($localeNotExists) > 0) {
                $validator = Validator::make([], []);
                // Add the locale check to the validator
                $validator->after(function ($validator) use ($localeNotExists) {
                    $validator->errors()->add('locale', trans('admin::app.validations.invalid-locale', ['locales' => json_encode($localeNotExists)]));
                });

                if ($validator->fails()) {
                    return $this->validateErrorResponse($validator);
                }
            }
        }

        return $next($request);
    }

    /**
     * This function retrieves the locales from the request data.
     * It first checks for locales under the 'labels' key. If not found, it checks under the 'locale_specific' key.
     *
     * @return array|null
     */
    public function getLocales()
    {
        $requestData = request()->all();
        $locales = $this->checkKeyExists($requestData, 'labels');
        if (! $locales) {
            $locales = $this->checkKeyExists($requestData, 'locale_specific');
        }

        return $locales;
    }

    /**
     * This function checks if a given key exists in an array and returns the unique keys of the nested arrays.
     * If the key is found in the top-level array, it returns the unique keys of the nested array under that key.
     * If the key is not found in the top-level array, it recursively checks each sub-array.
     *
     * @param  array  $array  The array to search in.
     * @param  string  $key  The key to search for.
     * @return array The unique keys of the nested arrays under the given key.
     */
    public function checkKeyExists(array $array, string $key)
    {
        $locales = [];
        if (array_key_exists($key, $array)) {
            return array_unique(array_keys($array[$key]));
        }
        foreach ($array as $index => $element) {
            if (is_array($element)) {
                if (array_key_exists($key, $element)) {
                    $locales = array_merge($element[$key], $locales);
                } else {
                    $this->checkKeyExists($element, $key);
                }
            }
        }

        return array_unique(array_keys($locales));
    }
}
