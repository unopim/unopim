<?php

namespace Webkul\MagicAI\Jobs\Concerns;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Core\Traits\HtmlPurifier;

trait PurifiesTranslatedContent
{
    use HtmlPurifier;

    /**
     * Run a translated rich-text value through the same allowlist the product save path applies.
     *
     * The content comes from the client, so a wysiwyg value must never reach storage unpurified.
     */
    protected function purifyTranslatedContent(Attribute $attribute, mixed $content): mixed
    {
        if (
            is_string($content)
            && $content !== ''
            && $attribute->type === 'textarea'
            && $attribute->enable_wysiwyg
        ) {
            return $this->purifyText($content);
        }

        return $content;
    }
}
