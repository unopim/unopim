<?php

namespace Webkul\Core;

/**
 * A per-request/per-job key-value memo. Bound scoped, so it is fresh for every
 * HTTP request (including under Octane) and reset between queue jobs — unlike a
 * memo kept on the request instance, which persists for a whole worker process.
 */
class RequestMemo
{
    /**
     * @var array<string, mixed>
     */
    private array $store = [];

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function get(string $key): mixed
    {
        return $this->store[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }

    public function forget(string $prefix): void
    {
        foreach (array_keys($this->store) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->store[$key]);
            }
        }
    }
}
