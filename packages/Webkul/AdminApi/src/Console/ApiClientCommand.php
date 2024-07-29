<?php

namespace Webkul\AdminApi\Console;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Console\ClientCommand as Passport;
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
     *
     * @return void
     */
    public function handle(ClientRepository $clients)
    {
        $this->createPasswordClient($clients);
    }

    /**
     * Create a new password grant client.
     *
     * @return void
     */
    protected function createPasswordClient(ClientRepository $clients)
    {
        $userName = $this->option('user_name') ?: $this->ask(
            'Which user Name should the client be assigned to?'
        );

        $user = $this->adminRepository->findByField('email', $userName)->first();
        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $name = $this->option('name') ?: $this->ask(
            'What should we name the password grant client?',
            config('app.name').' Password Grant Client'
        );

        $providers = array_keys(config('auth.providers'));

        $provider = $providers[0];

        $client = $clients->createPasswordGrantClient(
            $user->id, $name, 'http://localhost', $provider
        );

        $this->components->info('Password grant client created successfully.');

        $this->outputClientDetails($client);
    }
}
