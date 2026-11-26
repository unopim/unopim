<?php

namespace Webkul\Core\Traits;

use HTMLPurifier as BaseHTMLPurifier;
use HTMLPurifier_Config;
use Webkul\Core\HtmlPurifier\RichTextEmbeds;
use Webkul\Core\HtmlPurifier\SandboxIframe;

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
     *
     * Iframes are kept only for the configured embed URL prefixes and are always sandboxed;
     * links may open in a new window only, and then always get rel="noopener noreferrer".
     */
    protected function htmlPurifierConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));
        $config->set('HTML.DefinitionID', 'unopim-rich-text');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('HTML.Allowed', 'p,b,a[href|target],i,em,strong,ul,ol,li,br,img[src|alt|width|height],h2,h3,h4,table,thead,tbody,tr,th,td,iframe[src|width|height|title|frameborder]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetNoopener', true);
        $config->set('HTML.TargetNoreferrer', true);
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', RichTextEmbeds::sourcePattern());
        $config->set('HTML.SafeObject', true);

        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addAttribute('iframe', 'src*', 'URI#embedded');
            $definition->addBlankElement('iframe')->attr_transform_post[] = new SandboxIframe;
        }

        return $config;
    }
}
