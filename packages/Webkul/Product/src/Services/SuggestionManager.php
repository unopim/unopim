<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\Suggester;

class SuggestionManager
{
    public function resolve(string $key): ?Suggester
    {
        $config = config("suggesters.$key");

        if (empty($config['class'])) {
            return null;
        }

        return app($config['class']);
    }

    /**
     * AI is double-gated: a MagicAI system-config toggle AND an ACL permission.
     */
    public function canUseAi(string $key): bool
    {
        $config = config("suggesters.$key");

        if (empty($config)) {
            return false;
        }

        $suggester = $this->resolve($key);

        if (! $suggester || ! $suggester->supportsAi()) {
            return false;
        }

        $enabled = ! empty($config['config']) && (bool) core()->getConfigData($config['config']);

        $allowed = empty($config['acl']) || bouncer()->hasPermission($config['acl']);

        return $enabled && $allowed;
    }

    public function suggest(string $key, array $context, bool $useAi = false): array
    {
        $suggester = $this->resolve($key);

        if (! $suggester) {
            return [];
        }

        if ($useAi && $this->canUseAi($key)) {
            try {
                $suggestion = $suggester->suggestByAi($context);

                if (! empty($suggestion)) {
                    return $suggestion;
                }
            } catch (\Throwable $e) {
                // Fall through to the rule-based suggestion.
            }
        }

        return $suggester->suggestByRules($context);
    }
}
