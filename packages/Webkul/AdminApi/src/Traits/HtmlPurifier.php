<?php

namespace Webkul\AdminApi\Traits;

use HTMLPurifier as BaseHTMLPurifier;
use HTMLPurifier_Config;

trait HtmlPurifier
{
    /**
     * TextareaPurify function to sanitize the textarea input
     */
    public function textareaPurify(mixed $value): mixed
    {
        $value = htmlspecialchars_decode($value, ENT_QUOTES);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,img[src|alt|width|height],h2,h3,h4,table,thead,tbody,tr,th,td');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('HTML.SafeIframe', true);
        $config->set('HTML.SafeObject', true);

        $purifier = new BaseHTMLPurifier($config);

        return $purifier->purify($value);
    }
}
