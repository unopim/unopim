<?php

namespace Webkul\AdminApi\Console;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Console\ClientCommand as Passport;
use RuntimeException;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

class ApiClientCommand extends Passport
{
    public function __construct(protected AdminRepository $adminRepository)
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:passport:client
            {--user_name= : The user Name the client should be assigned to }
            {--name= : The name of the client}
            {--provider= : The name of the user provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client for issuing access tokens';

    /**
     * Execute the console command.
     */
    public function handle(ClientRepository $clients): void
    {
        $this->createPasswordClient($clients);
    }

    /**
     * Create a new password grant client.
     */
    protected function createPasswordClient(ClientRepository $clients): Client
    {
        $userName = $this->option('user_name') ?: $this->ask(
            'Which user Name should the client be assigned to?'
        );

        $user = $this->adminRepository->findByField('email', $userName)->first();
        if (! $user) {
            $this->error('User not found.');

            throw new RuntimeException('User not found.');
        }

        $name = $this->option('name') ?: $this->ask(
            'What should we name the password grant client?',
            config('app.name').' Password Grant Client'
        );

        $provider = config('auth.guards.api.provider', 'admins');

        // Passport 13 dropped the $userId + $redirect args from
        // createPasswordGrantClient. Create the client, then attach the
        // owner morph (owner_type / owner_id) for Passport 13's polymorphic
        // owner() relation and keep the legacy user_id column populated for
        // backwards-compat with ApiKeysDataGrid JOINs and Client::admins().
        $client = $clients->createPasswordGrantClient(
            $name,
            $provider,
            confidential: true,
        );

        $client->forceFill([
            'user_id'    => $user->id,
            'owner_type' => Admin::class,
            'owner_id'   => $user->id,
        ])->save();

        $this->components->info('Password grant client created successfully.');

        $this->outputClientDetails($client);

        return $client;
    }
}
