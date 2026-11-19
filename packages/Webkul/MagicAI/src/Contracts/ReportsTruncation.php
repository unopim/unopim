<?php

declare(strict_types=1);

namespace Webkul\MagicAI\Contracts;

use Webkul\MagicAI\Responses\GeneratedContent;

/**
 * Implemented by adapters that can tell a token-ceiling stop apart from a
 * natural one, so the caller can warn instead of silently keeping a half
 * sentence.
 */
interface ReportsTruncation
{
    public function askResult(): GeneratedContent;
}
