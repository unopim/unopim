<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Services;

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
     */
    const LEGACY_PATH = __DIR__.'/../Secrets/Oauth';

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
     * Equal to path() in every deployment that has run unopim:passport:keys.
     * An install upgraded by hand, without the installer or the upgrade
     * command, still has its pair in the previous location, and reading it
     * there keeps already-issued tokens valid until the command is run. The
     * fallback is read-only; nothing is ever written back to that directory.
     */
    public function resolvedPath(): string
    {
        $path = $this->path();

        if ($this->exists()) {
            return $path;
        }

        $legacyPath = $this->legacyPath();

        if (
            file_exists($legacyPath.'/'.self::PRIVATE_KEY)
            && file_exists($legacyPath.'/'.self::PUBLIC_KEY)
        ) {
            return $legacyPath;
        }

        return $path;
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
     * Copies a keypair written by an earlier release into the current location.
     *
     * Only bare-metal installs benefit: a container upgrade replaces the image
     * layer holding the old directory before this runs, so there is nothing
     * left to adopt and a fresh pair is generated instead.
     */
    public function adoptLegacyKeys(): bool
    {
        $legacyPrivateKey = $this->legacyPath().'/'.self::PRIVATE_KEY;
        $legacyPublicKey = $this->legacyPath().'/'.self::PUBLIC_KEY;

        if (! file_exists($legacyPrivateKey) || ! file_exists($legacyPublicKey)) {
            return false;
        }

        if (
            ! @copy($legacyPrivateKey, $this->privateKeyPath())
            || ! @copy($legacyPublicKey, $this->publicKeyPath())
        ) {
            return false;
        }

        $this->restrictPermissions();

        return true;
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
