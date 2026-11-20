<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Services;

use RuntimeException;

/**
 * Owns the location and creation of Passport's RSA signing keypair.
 *
 * The pair lives on the storage volume rather than in the package source so a
 * single deployment shares one key across every container and replica, and
 * replacing the application code no longer invalidates every issued token.
 *
 * Generation runs from the installer and the container entrypoint rather than
 * from a service provider, so it happens once per deployment instead of once
 * per request and cannot race between containers booting in parallel.
 */
class OauthKeyStore
{
    /**
     * Path a release before the keys moved onto the storage volume wrote to.
     *
     * Never read: keys left there by an older release are not trusted.
     */
    const LEGACY_PATH = __DIR__.'/../Secrets/Oauth';

    /**
     * SHA-256 of public keys, whitespace removed, that must never sign tokens again.
     */
    const REVOKED_PUBLIC_KEYS = [
        '0c8c5a9800abfe66f884a6567648f9426ded406bcf61e4f853965b912a8306e8',
    ];

    const PRIVATE_KEY = 'oauth-private.key';

    const PUBLIC_KEY = 'oauth-public.key';

    /**
     * Resolves the directory holding the keypair.
     *
     * The default is applied here rather than in the config file because
     * config:cache would otherwise bake in an absolute path from whichever
     * machine cached the config, which is not necessarily the one running it.
     */
    public function path(): string
    {
        $configured = config('api.oauth_key_path');

        return $configured
            ? rtrim((string) $configured, '/')
            : storage_path('app/private/oauth');
    }

    /**
     * Resolves the directory Passport should read from.
     *
     * @deprecated Use path(); keys are only ever read from the configured location.
     */
    public function resolvedPath(): string
    {
        return $this->path();
    }

    /**
     * Normalises the constant so error messages and Passport's own exceptions
     * quote a plain directory rather than one containing a parent traversal.
     */
    public function legacyPath(): string
    {
        return realpath(self::LEGACY_PATH) ?: self::LEGACY_PATH;
    }

    public function privateKeyPath(): string
    {
        return $this->path().'/'.self::PRIVATE_KEY;
    }

    public function publicKeyPath(): string
    {
        return $this->path().'/'.self::PUBLIC_KEY;
    }

    public function exists(): bool
    {
        return file_exists($this->privateKeyPath()) && file_exists($this->publicKeyPath());
    }

    /**
     * Whether the stored public key is one that must be replaced.
     */
    public function isRevoked(): bool
    {
        $publicKey = @file_get_contents($this->publicKeyPath());

        return $publicKey !== false && $this->isRevokedKey($publicKey);
    }

    /**
     * Stops Passport from signing or verifying with a missing or revoked keypair.
     *
     * Keys supplied through Passport's own config replace the files, so they are checked instead.
     *
     * @throws RuntimeException when the keypair is missing or revoked
     */
    public function ensureExists(): void
    {
        $configuredPublicKey = config('passport.public_key');

        $usable = config('passport.private_key') && $configuredPublicKey
            ? ! $this->isRevokedKey(str_replace('\\n', "\n", (string) $configuredPublicKey))
            : $this->exists() && ! $this->isRevoked();

        if (! $usable) {
            throw new RuntimeException(trans('admin::app.configuration.integrations.oauth-keys-missing'));
        }
    }

    /**
     * Whether the PEM public key matches a revoked one.
     */
    protected function isRevokedKey(string $publicKey): bool
    {
        return in_array(hash('sha256', (string) preg_replace('/\s+/', '', $publicKey)), $this->revokedPublicKeys(), true);
    }

    /**
     * Fingerprints of public keys that must never sign tokens again.
     *
     * @return list<string>
     */
    protected function revokedPublicKeys(): array
    {
        return self::REVOKED_PUBLIC_KEYS;
    }

    /**
     * Creates the key directory, returning false when the filesystem refuses.
     *
     * Filesystem warnings are suppressed throughout this class because Laravel
     * promotes them to ErrorException, which would abort the caller instead of
     * letting it report the failure and carry on.
     */
    public function makeDirectory(): bool
    {
        $path = $this->path();

        if (is_dir($path)) {
            return true;
        }

        return @mkdir($path, 0700, true) || is_dir($path);
    }

    /**
     * Writes a fresh RSA pair, returning false when OpenSSL is unavailable or
     * refuses to generate.
     */
    public function generate(): bool
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            return false;
        }

        openssl_pkey_export($resource, $privateKey);

        $details = openssl_pkey_get_details($resource);

        if ($details === false) {
            return false;
        }

        if (
            @file_put_contents($this->privateKeyPath(), $privateKey, LOCK_EX) === false
            || @file_put_contents($this->publicKeyPath(), $details['key'], LOCK_EX) === false
        ) {
            return false;
        }

        $this->restrictPermissions();

        return true;
    }

    protected function restrictPermissions(): void
    {
        chmod($this->privateKeyPath(), 0600);
        chmod($this->publicKeyPath(), 0600);
    }
}
