<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Console;

use Illuminate\Console\Command;
use Webkul\AdminApi\Services\OauthKeyStore;

class PassportKeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:passport:keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the Passport signing keypair when the deployment does not already have one';

    /**
     * Existing keys are never replaced: regenerating invalidates every access
     * and refresh token already issued, so rotation has to be a deliberate act
     * rather than a side effect of running the installer again.
     */
    public function handle(OauthKeyStore $keys): int
    {
        if ($keys->exists()) {
            $this->info('Passport keys already exist at '.$keys->path().'.');

            return self::SUCCESS;
        }

        if (! $keys->makeDirectory()) {
            $this->error('Unable to create '.$keys->path().'.');
            $this->line('Grant the application write access to that directory, or point UNOPIM_OAUTH_KEY_PATH somewhere writable.');

            return self::FAILURE;
        }

        if ($keys->adoptLegacyKeys()) {
            $this->info('Migrated the existing Passport keypair to '.$keys->path().'.');

            return self::SUCCESS;
        }

        if (! $keys->generate()) {
            $this->error('Unable to generate a Passport signing keypair.');
            $this->line('Verify that the OpenSSL extension is installed and that '.$keys->path().' is writable.');

            return self::FAILURE;
        }

        $this->info('Generated a Passport signing keypair at '.$keys->path().'.');

        return self::SUCCESS;
    }
}
