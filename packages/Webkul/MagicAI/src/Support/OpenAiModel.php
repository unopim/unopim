<?php

namespace Webkul\MagicAI\Support;

class OpenAiModel
{
    /**
     * Determine if the model is an OpenAI reasoning model (o-series, gpt-5 and later).
     *
     * Reasoning models reject `temperature`. Every generation from gpt-5 on is
     * treated as reasoning so a new release omits the parameter instead of
     * failing with a 400; only gpt-5-chat is known to still accept it. Two-digit
     * versions stop at 19 so Azure's `gpt-35-turbo` (GPT-3.5) keeps its temperature.
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
