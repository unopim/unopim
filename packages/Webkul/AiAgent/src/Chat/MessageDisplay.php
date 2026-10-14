<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Validation\ValidationException;

/** Inert native result data only: never authorization or executable tool instructions. */
class MessageDisplay
{
    public function normalize(array $message): array
    {
        $data = array_intersect_key($message, array_flip(['result', 'product_url', 'download_url', '_confirmed', 'action']));
        if (isset($data['result'])) {
            if (! is_array($data['result']) || strlen(json_encode($data['result'])) > 65536) {
                $this->invalid();
            }
            $nodes = 0;
            $this->value($data['result'], 0, $nodes);
        }
        foreach (['product_url', 'download_url'] as $field) {
            if (isset($data[$field]) && ! $this->safeUrl($data[$field], $field === 'product_url')) {
                $this->invalid();
            }
        }
        if (isset($data['_confirmed']) && ! is_bool($data['_confirmed'])) {
            $this->invalid();
        }
        if (isset($data['action']) && (! is_string($data['action']) || ! preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $data['action']))) {
            $this->invalid();
        }

        return array_filter($data, fn ($value) => $value !== null);
    }

    private function value(mixed $value, int $depth, int &$nodes): void
    {
        if (++$nodes > 2048 || $depth > 8) {
            $this->invalid();
        }
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                if (strlen((string) $key) > 128 || in_array($key, ['__proto__', 'prototype', 'constructor'], true)) {
                    $this->invalid();
                }
                $this->value($child, $depth + 1, $nodes);
            }
        } elseif (! is_null($value) && ! is_scalar($value) || is_string($value) && strlen($value) > 16384) {
            $this->invalid();
        }
    }

    private function safeUrl(mixed $url, bool $product): bool
    {
        if (! is_string($url) || strlen($url) > 8192 || (preg_match('/[\x00-\x20\x7f\\\\]/', $url) || preg_match('/[\x00-\x1f\x7f\\\\]/', rawurldecode($url)))) {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with(rawurldecode($url), '//')) {
            return true;
        }
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        if (! $product) {
            return true;
        }
        $origin = parse_url(config('app.url'));

        return strtolower($parts['host']) === strtolower($origin['host'] ?? '') && $parts['scheme'] === ($origin['scheme'] ?? '') && ($parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80)) === ($origin['port'] ?? (($origin['scheme'] ?? '') === 'https' ? 443 : 80));
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['messages' => trans('ai-agent::app.common.invalid-data')]);
    }
}
