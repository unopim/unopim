<?php

namespace Webkul\AiAgent\Http\Client;

/**
 * Bearer token authentication for AI provider APIs.
 */
class BearerToken
{
    public function __construct(
        protected string $token,
    ) {}

    /**
     * Apply Bearer token authentication to a cURL handle.
     *
     * @param  resource|\CurlHandle  $ch
     */
    public function apply($ch): void
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->token,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }
}
