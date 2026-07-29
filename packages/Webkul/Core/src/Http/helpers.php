<?php

use Intervention\Image\ImageManager;
use Webkul\Core\Core;

if (! function_exists('core')) {
    /**
     * Core helper.
     *
     * @return Core
     */
    function core()
    {
        return resolve('core');
    }
}

if (! function_exists('array_permutation')) {
    /**
     * @return mixed[]
     */
    function array_permutation($input): array
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if ($results === []) {
                foreach ($values as $value) {
                    $results[] = [$key => $value];
                }
            } else {
                $append = [];

                foreach ($results as &$result) {
                    $result[$key] = array_shift($values);

                    $copy = $result;

                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, $result[$key]);
                }

                $results = array_merge($results, $append);
            }
        }

        return $results;
    }
}

if (! function_exists('clean_content')) {
    /**
     * Sanitize content by stripping Blade directives, PHP tags, and
     * running through HTMLPurifier for XSS prevention.
     */
    function clean_content(?string $content): string
    {
        if (in_array($content, [null, '', '0'], true)) {
            return '';
        }

        // Strip Blade directives: @php, @if, @foreach, etc.
        $content = preg_replace('/@\w+(\s*\(.*?\))?/s', '', $content);

        // Strip Blade echo syntax: {{ }}, {!! !!}
        $content = preg_replace('/\{\{.*?\}\}/s', '', (string) $content);
        $content = preg_replace('/\{!!.*?!!\}/s', '', (string) $content);

        // Strip PHP tags
        $content = preg_replace('/<\?(?:php|=).*?\?>/s', '', (string) $content);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,a[href|title],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,pre,code,img[src|alt|width|height],table,thead,tbody,tr,th,td,span,div');

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($content);
    }
}

if (! function_exists('form_control_id')) {
    /**
     * Build the DOM id a form control renders for the given field name, so labels
     * can point their `for` attribute at it. Field names carry array syntax
     * (`values[common][sku]`) which is not usable in a CSS selector.
     */
    function form_control_id(?string $name, ?string $suffix = null): string
    {
        $toToken = fn (?string $value): string => trim((string) preg_replace('/[^A-Za-z0-9_.:-]+/', '_', (string) $value), '_');

        $id = $toToken($name);

        if (! in_array($suffix, [null, '', '0'], true)) {
            $id = trim($id.'_'.$toToken($suffix), '_');
        }

        return $id;
    }
}

if (! function_exists('unique_form_control_id')) {
    /**
     * Reserve a DOM id for the current request, suffixing repeats so that pages
     * rendering the same field name in several forms (a page form plus its
     * modals) do not emit duplicate ids.
     */
    function unique_form_control_id(string $id, bool $allowSuffix = true): string
    {
        if ($id === '') {
            return $id;
        }

        $request = request();

        $used = $request->attributes->get('form_control_ids', []);

        $used[$id] = ($used[$id] ?? 0) + 1;

        $request->attributes->set('form_control_ids', $used);

        return $allowSuffix && $used[$id] > 1 ? $id.'_'.$used[$id] : $id;
    }
}

if (! function_exists('image_manager')) {
    /**
     * Get the image manager instance.
     */
    function image_manager(): ImageManager
    {
        return resolve('image_manager');
    }
}
