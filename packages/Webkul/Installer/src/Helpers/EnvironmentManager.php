<?php

namespace Webkul\Installer\Helpers;

use Exception;

class EnvironmentManager
{
    /**
     * Create a helper instance.
     */
    public function __construct(protected DatabaseManager $databaseManager) {}

    /**
     * Path of the operator-authored .env file.
     */
    protected function envPath(): string
    {
        return base_path('.env');
    }

    /**
     * Confirm the environment is ready without ever writing to it.
     *
     * The installer used to rewrite .env from unauthenticated wizard requests,
     * which turned an exposed pre-install instance into a configuration
     * backdoor. The environment is now operator-managed — a hand-authored .env
     * or server-level variables (FTP/panel hosting has no file at all). The
     * single permitted write is `artisan key:generate` into an existing .env,
     * and DatabaseManager skips even that when APP_KEY is already set; without
     * a file there is nowhere to write a key, so one must be provided.
     */
    public function generateEnv(array $request): bool|Exception
    {
        if (file_exists($this->envPath())) {
            try {
                $this->databaseManager->generateKey();

                return true;
            } catch (Exception $e) {
                return $e;
            }
        }

        if (empty(config('app.key'))) {
            return new Exception(trans('installer::app.installer.index.environment-configuration.app-key-missing'));
        }

        return true;
    }
}
