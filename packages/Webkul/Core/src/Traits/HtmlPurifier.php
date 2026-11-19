<?php

namespace Webkul\Core\Traits;

use HTMLPurifier as BaseHTMLPurifier;
use HTMLPurifier_Config;

trait HtmlPurifier
{
    private ?BaseHTMLPurifier $renderedHtmlPurifier = null;

    /**
     * purifyText function to sanitize the textarea input
     */
    public function purifyText(mixed $value): mixed
    {
        $value = htmlspecialchars_decode((string) $value, ENT_QUOTES);

        $purifier = new BaseHTMLPurifier($this->htmlPurifierConfig());

        return $purifier->purify($value);
    }

    /**
     * Purify rendered template HTML with a purifier instance that is reused across calls.
     */
    public function purifyRenderedHtml(string $value): string
    {
        $this->renderedHtmlPurifier ??= new BaseHTMLPurifier($this->htmlPurifierConfig());

        return $this->renderedHtmlPurifier->purify($value);
    }

    /**
     * Get the purifier configuration shared by the text and rendered template purifiers.
     */
    protected function htmlPurifierConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));
        $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,img[src|alt|width|height],h2,h3,h4,table,thead,tbody,tr,th,td');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('HTML.SafeIframe', true);
        $config->set('HTML.SafeObject', true);

        return $config;
    }
}
