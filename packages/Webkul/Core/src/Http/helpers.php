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
        return app('core');
    }
}

if (! function_exists('array_permutation')) {
    function array_permutation($input)
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if (empty($results)) {
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
        if (empty($content)) {
            return '';
        }

        // Strip Blade directives: @php, @if, @foreach, etc.
        $content = preg_replace('/@\w+(\s*\(.*?\))?/s', '', $content);

        // Strip Blade echo syntax: {{ }}, {!! !!}
        $content = preg_replace('/\{\{.*?\}\}/s', '', $content);
        $content = preg_replace('/\{!!.*?!!\}/s', '', $content);

        // Strip PHP tags
        $content = preg_replace('/<\?(?:php|=).*?\?>/s', '', $content);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,a[href|title],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,pre,code,img[src|alt|width|height],table,thead,tbody,tr,th,td,span,div');

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($content);
    }
}

if (! function_exists('image_manager')) {
    /**
     * Get the image manager instance.
     */
    function image_manager(): ImageManager
    {
        return app('image_manager');
    }
}
