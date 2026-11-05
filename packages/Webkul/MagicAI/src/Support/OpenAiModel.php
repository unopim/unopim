<?php

namespace Webkul\MagicAI\Support;

class OpenAiModel
{
    /**
     * Determine if the model is an OpenAI reasoning model (o-series, gpt-5 and later).
     *
     * Chat variants such as "gpt-5.1-chat-latest" still accept temperature.
     *
     * Azure names GPT-3.5 deployments "gpt-35-turbo", so major version 35 is excluded.
     */
    public static function isReasoning(string $model): bool
    {
        $model = strtolower($model);

        if (preg_match('/^gpt-\d+(\.\d+)?-chat/', $model)) {
            return false;
        }

        if (preg_match('/^chat-latest|^o[1-9]\b|^o[1-9]-/', $model)) {
            return true;
        }

        if (! preg_match('/^gpt-(\d+)/', $model, $matches)) {
            return false;
        }

        $major = (int) $matches[1];

        return $major >= 5 && $major !== 35;
    }
}
