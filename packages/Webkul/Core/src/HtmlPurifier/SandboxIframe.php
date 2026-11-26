<?php

namespace Webkul\Core\HtmlPurifier;

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;

/**
 * Locks every allowed embed into a sandbox, whatever attributes the author sent.
 */
class SandboxIframe extends HTMLPurifier_AttrTransform
{
    public const SANDBOX = 'allow-scripts allow-same-origin allow-presentation allow-popups';

    public const REFERRER_POLICY = 'strict-origin-when-cross-origin';

    /**
     * Add the sandbox, referrer policy and fullscreen permission to an iframe.
     *
     * @param  array<string, string>  $attr
     * @param  HTMLPurifier_Config  $config
     * @param  HTMLPurifier_Context  $context
     * @return array<string, string>
     */
    public function transform($attr, $config, $context): array
    {
        $attr['sandbox'] = self::SANDBOX;
        $attr['referrerpolicy'] = self::REFERRER_POLICY;
        $attr['allowfullscreen'] = 'allowfullscreen';
        $attr['loading'] = 'lazy';

        return $attr;
    }
}
