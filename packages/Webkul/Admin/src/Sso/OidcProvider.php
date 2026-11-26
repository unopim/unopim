<?php

namespace Webkul\Admin\Sso;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;
use Webkul\Admin\Contracts\SsoLogoutProvider;

class OidcProvider extends AbstractOAuthProvider implements SsoLogoutProvider
{
    /**
     * Seconds of clock difference tolerated on `exp`, `iat` and `nbf`.
     */
    public const CLOCK_SKEW_SECONDS = 60;

    /**
     * Seconds the discovery document and the signing keys are cached.
     */
    public const CACHE_TTL_SECONDS = 3600;

    public const ID_TOKEN_SESSION_KEY = 'sso_oidc.id_token';

    /**
     * Accepted ID token algorithms and the key type each one needs. Symmetric
     * algorithms and `none` are refused, so a client secret can never stand in
     * for the provider's signature.
     */
    protected const SIGNING_ALGORITHMS = [
        'RS256' => 'RSA',
        'ES256' => 'EC',
    ];

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $idTokenClaims = null;

    protected ?string $idToken = null;

    public function __construct(protected readonly OidcSettings $settings) {}

    /**
     * Driver code used in the SSO routes and the admin's `sso_provider`.
     */
    public function getCode(): string
    {
        return 'oidc';
    }

    /**
     * Sign-in button label.
     */
    public function getLabel(): string
    {
        return $this->settings->label();
    }

    /**
     * Blade view of the sign-in button icon.
     */
    public function getIconView(): ?string
    {
        return 'admin::components.sso.icons.oidc';
    }

    /**
     * Position of the sign-in button among the enabled drivers.
     */
    public function getSort(): int
    {
        return 10;
    }

    /**
     * Whether the provider is switched on and fully configured.
     */
    public function isEnabled(): bool
    {
        return $this->settings->isEnabled();
    }

    /**
     * Without a usable discovery document there is no endpoint to send the
     * visitor to, so they are returned to the login page instead.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->discovery()) {
            session()->flash('error', trans('admin::app.settings.users.login-error'));

            return redirect()->route('admin.session.create');
        }

        return parent::redirect($request);
    }

    /**
     * The verified raw ID token of the sign-in handled in this request, kept as
     * the `id_token_hint` for RP-initiated logout.
     */
    public function verifiedIdToken(): ?string
    {
        return $this->idToken;
    }

    /**
     * Provider end-session URL for a session opened through OpenID Connect, or null to log out locally only.
     */
    public function logoutUrl(Request $request): ?string
    {
        $idToken = $request->session()->pull(self::ID_TOKEN_SESSION_KEY);

        if (! is_string($idToken) || $idToken === '' || ! $this->settings->performsRpLogout()) {
            return null;
        }

        $endpoint = $this->discovery()['end_session_endpoint'] ?? null;

        if (! is_string($endpoint) || $endpoint === '') {
            return null;
        }

        return $this->appendQuery($endpoint, [
            'id_token_hint'            => $idToken,
            'post_logout_redirect_uri' => route('admin.session.create'),
            'client_id'                => $this->settings->clientId(),
        ]);
    }

    /**
     * Authorization endpoint from discovery with the code flow, PKCE, state and nonce parameters.
     */
    protected function buildAuthorizationUrl(string $state, string $codeChallenge, string $nonce): string
    {
        return $this->appendQuery((string) $this->discovery()['authorization_endpoint'], [
            'client_id'             => $this->settings->clientId(),
            'response_type'         => 'code',
            'redirect_uri'          => $this->getCallbackUrl(),
            'scope'                 => implode(' ', $this->settings->scopes()),
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Uses `client_secret_post` when the provider advertises it and
     * `client_secret_basic` otherwise, which is also the OIDC default when the
     * provider advertises no method at all.
     */
    protected function exchangeCodeForToken(string $authorizationCode, string $codeVerifier): ?SsoToken
    {
        $discovery = $this->discovery();

        if (! $discovery) {
            return null;
        }

        $payload = [
            'grant_type'    => 'authorization_code',
            'code'          => $authorizationCode,
            'redirect_uri'  => $this->getCallbackUrl(),
            'code_verifier' => $codeVerifier,
            'client_id'     => $this->settings->clientId(),
        ];

        $request = Http::asForm()->acceptJson()->timeout(10);

        if (in_array('client_secret_post', (array) ($discovery['token_endpoint_auth_methods_supported'] ?? []), true)) {
            $payload['client_secret'] = $this->settings->clientSecret();
        } else {
            $request = $request->withHeaders([
                'Authorization' => 'Basic '.base64_encode(
                    urlencode($this->settings->clientId()).':'.urlencode($this->settings->clientSecret())
                ),
            ]);
        }

        try {
            $response = $request->post($discovery['token_endpoint'], $payload);
        } catch (ConnectionException) {
            return null;
        }

        $accessToken = $response->json('access_token');

        $idToken = $response->json('id_token');

        if (! $response->successful() || ! is_string($accessToken) || ! is_string($idToken) || $idToken === '') {
            return null;
        }

        return new SsoToken(accessToken: $accessToken, idToken: $idToken);
    }

    /**
     * Verify the ID token and keep its claims and raw value for the identity and for logout.
     */
    protected function isTokenAcceptable(SsoToken $token, string $nonce): bool
    {
        $this->idTokenClaims = $this->verifyIdToken((string) $token->idToken, $nonce);

        $this->idToken = $this->idTokenClaims === null ? null : $token->idToken;

        return $this->idTokenClaims !== null;
    }

    /**
     * Builds the identity from the verified ID token, completed by the userinfo
     * endpoint when the token lacks the email, its verification flag, or the
     * groups a configured role map needs. The ID token's claims win over userinfo,
     * except that the email and its `email_verified` flag are always taken as a
     * pair from the one document that supplies the email, so a flag asserted for
     * one address can never vouch for another.
     */
    protected function fetchIdentity(SsoToken $token): ?SsoIdentity
    {
        $claims = array_filter($this->idTokenClaims ?? [], fn (mixed $value): bool => $value !== null);

        if (! isset($claims['sub'])) {
            return null;
        }

        if ($this->needsUserInfo($claims)) {
            $userInfo = $this->fetchUserInfo($token->accessToken) ?? [];

            if ($userInfo !== [] && ($userInfo['sub'] ?? null) !== $claims['sub']) {
                return null;
            }

            $emailSource = isset($claims['email']) ? $claims : $userInfo;

            $claims = array_merge($claims + $userInfo, [
                'email'          => $emailSource['email'] ?? null,
                'email_verified' => $emailSource['email_verified'] ?? null,
            ]);
        }

        $email = $claims['email'] ?? null;

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        if ($this->settings->requiresVerifiedEmail() && ($claims['email_verified'] ?? null) !== true) {
            return null;
        }

        $name = $claims['name'] ?? $claims['preferred_username'] ?? null;

        return new SsoIdentity(
            identifier: $claims['sub'],
            email: trim($email),
            name: is_string($name) && $name !== '' ? $name : null,
            raw: $claims,
        );
    }

    /**
     * Verify signature, issuer, audience, authorized party, lifetime and nonce,
     * returning the claims or null when any check fails.
     *
     * @return array<string, mixed>|null
     */
    protected function verifyIdToken(string $jwt, string $nonce): ?array
    {
        $discovery = $this->discovery();

        $header = $this->decodeTokenHeader($jwt);

        $algorithm = $header['alg'] ?? null;

        if (! $discovery || ! is_string($algorithm) || ! isset(self::SIGNING_ALGORITHMS[$algorithm])) {
            return null;
        }

        $kid = isset($header['kid']) && is_scalar($header['kid']) ? (string) $header['kid'] : null;

        $key = $this->signingKey($discovery, $algorithm, $kid);

        if (! $key) {
            return null;
        }

        $previousLeeway = JWT::$leeway;

        JWT::$leeway = self::CLOCK_SKEW_SECONDS;

        try {
            $claims = json_decode(json_encode(JWT::decode($jwt, $key)), true);
        } catch (Throwable) {
            return null;
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        return is_array($claims) && $this->claimsAreValid($claims, $discovery['issuer'], $nonce) ? $claims : null;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function claimsAreValid(array $claims, string $issuer, string $nonce): bool
    {
        $clientId = $this->settings->clientId();

        $audiences = (array) ($claims['aud'] ?? []);

        return ($claims['iss'] ?? null) === $issuer
            && in_array($clientId, $audiences, true)
            && (count($audiences) === 1 || ($claims['azp'] ?? null) === $clientId)
            && (! isset($claims['azp']) || $claims['azp'] === $clientId)
            && is_numeric($claims['exp'] ?? null)
            && is_numeric($claims['iat'] ?? null)
            && is_string($claims['nonce'] ?? null)
            && hash_equals($nonce, $claims['nonce'])
            && is_string($claims['sub'] ?? null)
            && $claims['sub'] !== '';
    }

    /**
     * Key for the token's `kid`. An unknown `kid` refetches the key set once,
     * because the provider may have rotated its keys since they were cached. A
     * token without `kid` is only accepted when exactly one key fits.
     *
     * @param  array<string, mixed>  $discovery
     */
    protected function signingKey(array $discovery, string $algorithm, ?string $kid): ?Key
    {
        $key = $this->pickKey($this->signingKeys($discovery, false), $algorithm, $kid);

        if (! $key && $kid !== null) {
            $key = $this->pickKey($this->signingKeys($discovery, true), $algorithm, $kid);
        }

        return $key;
    }

    /**
     * @param  array<int, array{kid: ?string, key: Key}>  $keys
     */
    protected function pickKey(array $keys, string $algorithm, ?string $kid): ?Key
    {
        $candidates = array_values(array_filter(
            $keys,
            fn (array $entry): bool => $entry['key']->getAlgorithm() === $algorithm
                && ($kid === null || $entry['kid'] === $kid)
        ));

        return count($candidates) === 1 ? $candidates[0]['key'] : null;
    }

    /**
     * Signature keys from the provider's key set. The raw key set is cached, not
     * the parsed keys, so any cache store can hold it.
     *
     * @param  array<string, mixed>  $discovery
     * @return array<int, array{kid: ?string, key: Key}>
     */
    protected function signingKeys(array $discovery, bool $refresh): array
    {
        $cacheKey = 'unopim.sso.oidc.jwks.'.hash('sha256', $discovery['issuer'].'|'.$discovery['jwks_uri']);

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $jwks = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn (): ?array => is_array($set = $this->fetchJson($discovery['jwks_uri'])) && is_array($set['keys'] ?? null) ? $set : null
        );

        $keys = [];

        foreach ((array) ($jwks['keys'] ?? []) as $jwk) {
            if (! is_array($jwk) || (isset($jwk['use']) && $jwk['use'] !== 'sig')) {
                continue;
            }

            $algorithm = $jwk['alg'] ?? array_search($jwk['kty'] ?? null, self::SIGNING_ALGORITHMS, true);

            if (! is_string($algorithm) || (self::SIGNING_ALGORITHMS[$algorithm] ?? null) !== ($jwk['kty'] ?? null)) {
                continue;
            }

            try {
                $key = JWK::parseKey([...$jwk, 'alg' => $algorithm]);
            } catch (Throwable) {
                continue;
            }

            if ($key instanceof Key) {
                $keys[] = ['kid' => isset($jwk['kid']) ? (string) $jwk['kid'] : null, 'key' => $key];
            }
        }

        return $keys;
    }

    /**
     * The provider's discovery document. The issuer must use https, since the
     * client secret and authorization codes travel to its endpoints. Its `issuer`
     * must name the configured issuer (a trailing slash on either side is
     * ignored); the ID token's `iss` is then compared exactly against the
     * document's value.
     *
     * @return array<string, mixed>|null
     */
    protected function discovery(): ?array
    {
        $issuer = $this->settings->issuer();

        if ($issuer === '' || ! str_starts_with(strtolower($issuer), 'https://')) {
            return null;
        }

        return Cache::remember(
            'unopim.sso.oidc.discovery.'.hash('sha256', $issuer),
            self::CACHE_TTL_SECONDS,
            function () use ($issuer): ?array {
                $document = $this->fetchJson(rtrim($issuer, '/').'/.well-known/openid-configuration');

                if (! is_array($document)) {
                    return null;
                }

                foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'jwks_uri'] as $required) {
                    if (! is_string($document[$required] ?? null) || $document[$required] === '') {
                        return null;
                    }
                }

                return rtrim($document['issuer'], '/') === rtrim($issuer, '/') ? $document : null;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function needsUserInfo(array $claims): bool
    {
        if (! is_string($this->discovery()['userinfo_endpoint'] ?? null)) {
            return false;
        }

        return ! isset($claims['email'], $claims['email_verified'])
            || ($this->settings->hasRoleMap() && ! isset($claims[$this->settings->groupsClaim()]));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchUserInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(10)
                ->get($this->discovery()['userinfo_endpoint']);
        } catch (ConnectionException) {
            return null;
        }

        $claims = $response->successful() ? $response->json() : null;

        return is_array($claims) ? $claims : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchJson(string $url): ?array
    {
        try {
            $response = Http::acceptJson()->timeout(10)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        $json = $response->successful() ? $response->json() : null;

        return is_array($json) ? $json : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeTokenHeader(string $jwt): array
    {
        $header = json_decode((string) base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/'), true), true);

        return is_array($header) ? $header : [];
    }

    /**
     * @param  array<string, string>  $query
     */
    protected function appendQuery(string $url, array $query): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }
}
