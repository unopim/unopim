---
name: unopim-http-client
description: >
  Create robust cURL-based HTTP client classes for Unopim third-party
  connectors. Covers ApiClient with retry logic, BasicAuth and OAuth token
  auth strategies, connection testing, error handling, and integration into
  the Service layer. Use this skill when building the HTTP client for any
  Unopim connector (WooCommerce, Shopify, Shopware, module, REST API).
  IMPORTANT: Unopim connectors use cURL directly, NOT Guzzle or Laravel HTTP.
version: "2.0.0"
tags: [unopim, http, curl, api-client, connector, integration, authentication]
---

# Unopim HTTP Client

## Overview

Unopim connectors use **native cURL** for HTTP communication — not Guzzle,
not Laravel's `Http` facade. This matches the production WooCommerce connector
reference (`ApiClient.php` / `BasicAuth.php`).

**Key rules:**
- Use `curl_init()` / `curl_setopt_array()` / `curl_exec()` / `curl_close()`
- Always check `curl_errno()` after exec
- Auth classes set cURL options on the handle — not HTTP headers separately
- An `ApiClient` class wraps all HTTP methods (get/post/put/delete)
- A `Service` class uses `ApiClient` — controllers never call `ApiClient` directly

**Admin UI compatibility rule:**
- If this skill also generates admin Blade setup or credential forms, use UnoPim form components (`x-admin::form.control-group`, `.label`, `.control`, `.error`) instead of raw HTML controls.

---

## 1. Auth: BasicAuth

Used by WooCommerce (consumer key + secret as HTTP Basic).

```php
<?php
// src/Http/Client/BasicAuth.php

namespace Webkul\{ModuleName}\Http\Client;

class BasicAuth
{
    /**
     * @param string $key    Consumer key / API key
     * @param string $secret Consumer secret / API secret
     */
    public function __construct(
        protected string $key,
        protected string $secret,
    ) {}

    /**
     * Apply Basic Auth credentials to a cURL handle.
     *
     * @param resource|\CurlHandle $ch
     */
    public function apply($ch): void
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->key . ':' . $this->secret);
    }
}
```

---

## 2. Auth: BearerToken (OAuth / JWT)

```php
<?php
// src/Http/Client/BearerToken.php

namespace Webkul\{ModuleName}\Http\Client;

class BearerToken
{
    public function __construct(protected string $token) {}

    /**
     * Apply Bearer token to a cURL handle.
     *
     * @param resource|\CurlHandle $ch
     */
    public function apply($ch): void
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }
}
```

---

## 3. ApiClient

```php
<?php
// src/Http/Client/ApiClient.php

namespace Webkul\{ModuleName}\Http\Client;

use Webkul\{ModuleName}\Exceptions\ApiException;

class ApiClient
{
    protected string $baseUrl = '';

    protected BasicAuth|BearerToken|null $auth = null;

    /**
     * Configure the client for a specific credential.
     */
    public function configure(string $baseUrl, string $key, string $secret): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->auth    = new BasicAuth($key, $secret);

        return $this;
    }

    /**
     * Configure with a Bearer token (e.g. after OAuth exchange).
     */
    public function configureWithToken(string $baseUrl, string $token): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->auth    = new BearerToken($token);

        return $this;
    }

    /**
     * Build a full API URL from an endpoint path.
     */
    public function buildApiUrl(string $endpoint): string
    {
        // Subclasses can override to add a version prefix, e.g. /wp-json/wc/v3/
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * Issue a GET request.
     *
     * @param  array<string,mixed> $params  Query parameters
     * @return array<mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildApiUrl($endpoint);

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        return $this->execute('GET', $url);
    }

    /**
     * Issue a POST request.
     *
     * @param  array<mixed> $data  JSON body
     * @return array<mixed>
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->execute('POST', $this->buildApiUrl($endpoint), $data);
    }

    /**
     * Issue a PUT request.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->execute('PUT', $this->buildApiUrl($endpoint), $data);
    }

    /**
     * Issue a DELETE request.
     *
     * @return array<mixed>
     */
    public function delete(string $endpoint): array
    {
        return $this->execute('DELETE', $this->buildApiUrl($endpoint));
    }

    /**
     * Execute a cURL request.
     *
     * @param  array<mixed>|null $data
     * @return array<mixed>
     *
     * @throws ApiException
     */
    protected function execute(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ];

        match ($method) {
            'POST'   => $options[CURLOPT_POST]      = true,
            'PUT'    => $options[CURLOPT_CUSTOMREQUEST] = 'PUT',
            'DELETE' => $options[CURLOPT_CUSTOMREQUEST] = 'DELETE',
            default  => null,
        };

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        // Apply auth strategy
        if ($this->auth) {
            $this->auth->apply($ch);
        }

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new ApiException("cURL error [{$errno}]: " . curl_strerror($errno));
        }

        if ($httpCode >= 400) {
            $body = json_decode($response ?: '', true) ?? [];
            $msg  = $body['message'] ?? "HTTP {$httpCode} from {$url}";
            throw new ApiException($msg, $httpCode);
        }

        return json_decode($response ?: '[]', true) ?? [];
    }
}
```

---

## 4. WooCommerce ApiClient (Subclass Example)

For WooCommerce REST API v3, the base URL includes the WP REST prefix:

```php
<?php
// src/Http/Client/WooCommerceApiClient.php

namespace Webkul\WooCommerce\Http\Client;

class WooCommerceApiClient extends ApiClient
{
    /**
     * WooCommerce API uses /wp-json/wc/v3/ prefix.
     */
    public function buildApiUrl(string $endpoint): string
    {
        return $this->baseUrl . '/wp-json/wc/v3/' . ltrim($endpoint, '/');
    }
}
```

---

## 5. ApiException

```php
<?php
// src/Exceptions/ApiException.php

namespace Webkul\{ModuleName}\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```

---

## 6. OAuth Token Client (for token-based APIs like Shopify)

```php
<?php
// src/Http/Client/OAuthClient.php

namespace Webkul\{ModuleName}\Http\Client;

use Webkul\{ModuleName}\Exceptions\ApiException;

class OAuthClient
{
    /**
     * Exchange authorization code for an access token.
     *
     * @return array{access_token: string, scope: string}
     * @throws ApiException
     */
    public function exchangeCode(
        string $shopDomain,
        string $clientId,
        string $clientSecret,
        string $code
    ): array {
        $url = "https://{$shopDomain}/admin/oauth/access_token";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'code'          => $code,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new ApiException('OAuth exchange cURL error: ' . curl_strerror($errno));
        }

        $data = json_decode($response ?: '', true) ?? [];

        if ($httpCode !== 200 || empty($data['access_token'])) {
            throw new ApiException('OAuth token exchange failed: ' . ($data['error'] ?? 'unknown'));
        }

        return $data;
    }
}
```

---

## 7. Service Provider Binding

Register `ApiClient` in the module ServiceProvider so it can be injected:

```php
// In {ModuleName}ServiceProvider::register()
$this->app->singleton(\Webkul\{ModuleName}\Http\Client\ApiClient::class);
$this->app->singleton(\Webkul\{ModuleName}\Services\{ModuleName}Service::class);
```

---

## 8. Usage in Service Layer

```php
<?php
// src/Services/{ModuleName}Service.php

namespace Webkul\{ModuleName}\Services;

use Webkul\{ModuleName}\Http\Client\ApiClient;
use Webkul\{ModuleName}\Models\Credential;

class {ModuleName}Service
{
    public function __construct(protected ApiClient $client) {}

    public function testConnection(string $apiUrl, string $key, string $secret): bool
    {
        $this->client->configure($apiUrl, $key, $secret);
        $response = $this->client->get('system_status');
        return isset($response['environment']);
    }

    public function useCredential(Credential $credential): static
    {
        $this->client->configure(
            $credential->apiUrl,
            $credential->consumerKey,
            $credential->consumerSecret,
        );
        return $this;
    }
}
```

---

## 9. Checklist

- [ ] `ApiClient` uses cURL (no Guzzle, no Laravel Http facade)
- [ ] Auth strategies (`BasicAuth`, `BearerToken`) are separate classes
- [ ] Auth classes call `curl_setopt()` directly on the handle
- [ ] Always call `curl_errno()` after `curl_exec()`
- [ ] Always call `curl_close()` after use
- [ ] HTTP errors (4xx/5xx) throw `ApiException` with code
- [ ] `buildApiUrl()` is overridable in subclasses for version-prefix APIs
- [ ] `ApiClient` and `Service` registered as singletons in ServiceProvider
- [ ] Controllers never use `ApiClient` directly — always via Service
- [ ] OAuth flow uses separate `OAuthClient` class
