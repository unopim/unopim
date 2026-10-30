<?php

namespace Webkul\MagicAI\Support;

class OpenAiModel
{
    /**
     * Determine if the model is an OpenAI reasoning model (o-series, gpt-5 and later).
     */
    public static function isReasoning(string $model): bool
    {
        $model = strtolower($model);

        if (str_starts_with($model, 'gpt-5-chat')) {
            return false;
        }

        return (bool) preg_match('/^chat-latest|^o[1-9]\b|^o[1-9]-|^gpt-([5-9]|1\d)/', $model);
    }
}
