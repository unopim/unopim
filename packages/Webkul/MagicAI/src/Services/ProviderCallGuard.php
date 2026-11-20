<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/**
 * Holds the outbound guard for the provider call currently in scope and
 * applies it to each Http client request through one global middleware.
 *
 * Bound as scoped, so the state lives for one request or job and never
 * crosses into another under Octane or a queue worker. With no call in
 * scope the middleware passes every request through untouched.
 */
class ProviderCallGuard
{
    /**
     * @var array{host: ?string, port: ?int, curl: array<int, list<string>>}|null
     */
    protected ?array $active = null;

    /**
     * Start guarding provider calls for a platform base URL, returning the
     * guard state to hand back to restore(). A URL that cannot be validated
     * and pinned refuses the call; without a URL only redirects are refused.
     *
     * @return array{host: ?string, port: ?int, curl: array<int, list<string>>}|null
     *
     * @throws RuntimeException
     */
    public function enter(?string $url): ?array
    {
        $guard = ['host' => null, 'port' => null, 'curl' => []];

        if ($url !== null && $url !== '') {
            $pinned = SafeWebhookUrl::pinnedHttpOptions($url);

            if ($pinned === null) {
                throw new RuntimeException(trans('admin::app.configuration.platform.message.unsafe-api-url'));
            }

            $guard = [
                'host' => strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]')),
                'port' => $this->port($url),
                'curl' => $pinned['curl'],
            ];
        }

        $previous = $this->active;

        $this->active = $guard;

        return $previous;
    }

    /**
     * Put back the guard state enter() replaced.
     *
     * @param  array{host: ?string, port: ?int, curl: array<int, list<string>>}|null  $previous
     */
    public function restore(?array $previous): void
    {
        $this->active = $previous;
    }

    /**
     * Guzzle middleware body: refuse redirects and pin the platform host for
     * a request made while a provider call is in scope. Guzzle's redirect
     * middleware sits outside this one, so a 3xx loses its Location header
     * here rather than relying on allow_redirects alone.
     *
     * @param  array<string, mixed>  $options
     */
    public function handle(callable $handler, RequestInterface $request, array $options): PromiseInterface
    {
        if ($this->active === null) {
            return $handler($request, $options);
        }

        $options['allow_redirects'] = false;

        if ($this->matches($request)) {
            $options['curl'] = array_replace($options['curl'] ?? [], $this->active['curl']);
        }

        return $handler($request, $options)->then(
            fn (ResponseInterface $response): ResponseInterface => $response->getStatusCode() >= 300 && $response->getStatusCode() < 400
                ? $response->withoutHeader('Location')
                : $response,
        );
    }

    /**
     * Whether the request goes to the pinned platform host and port.
     */
    protected function matches(RequestInterface $request): bool
    {
        $uri = $request->getUri();

        return $this->active['host'] !== null
            && strtolower(trim($uri->getHost(), '[]')) === $this->active['host']
            && ($uri->getPort() ?? ($uri->getScheme() === 'https' ? 443 : 80)) === $this->active['port'];
    }

    protected function port(string $url): int
    {
        $port = parse_url($url, PHP_URL_PORT);

        if (is_int($port)) {
            return $port;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80;
    }
}
