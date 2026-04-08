<?php

use Webkul\Installer\Console\Commands\Installer;
use Webkul\User\Models\Admin;

it('should ask for email if email option is not provided in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Provide Email of User', 'new.user@example.com')
        ->assertExitCode(0);
});

it('should ask for ui_locale if ui_locale option is not provided in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Please select the default application locale', 'en_US')
        ->assertExitCode(0);
});

it('should ask for timezone if timezone option is not provided in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--admin'     => false,
    ])
        ->expectsQuestion('Please select the default timezone', 'UTC')
        ->assertExitCode(0);
});

it('should ask for ui_locale for invalid ui_locale in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 58,
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Please select the default application locale', 'en_US')
        ->assertExitCode(0);
});

it('should ask for timezone for invalid timezone in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'invalid/timezone',
        '--admin'     => false,
    ])
        ->expectsQuestion('Please select the default timezone', 'UTC')
        ->assertExitCode(0);
});

it('should ask for name if name option is not provided in the command', function () {
    $this->artisan('unopim:user:create', [
        '--email'      => 'new.user@example.com',
        '--password'   => 'securepassword',
        '--ui_locale'  => 'en_US',
        '--timezone'   => 'UTC',
        '--admin'      => false,
    ])
        ->expectsQuestion('Set the Name for User', 'New User')
        ->assertExitCode(0);
});

it('should ask for password if password option is not provided in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Input a Secure Password for User', 'password')
        ->assertExitCode(0);
});

it('should ask for password if password length is less than 6 in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--password'  => 'pass',
        '--email'     => 'new.user@example.com',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Input a Secure Password for User', 'password')
        ->assertExitCode(0);
});

it('should create an admin user with command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'John Cena',
        '--email'     => 'john.cena@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => true,
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('admins', [
        'name'  => 'John Cena',
        'email' => 'john.cena@example.com',
    ]);
});

it('should create a user with command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('admins', [
        'name'  => 'New User',
        'email' => 'new.user@example.com',
    ]);
});

it('should not create a user if one with same already exists for same email with command', function () {
    Admin::factory()->create([
        'email' => 'new.user@example.com',
        'name'  => 'New User',
    ]);

    $this->artisan('unopim:user:create', [
        '--name'      => 'New User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Provide Email of User', 'admin@example.com')
        ->assertExitCode(1);
});

it('should ask for name if invalid user name given in the command', function () {
    $this->artisan('unopim:user:create', [
        '--name'      => 'New --User',
        '--email'     => 'new.user@example.com',
        '--password'  => 'securepassword',
        '--ui_locale' => 'en_US',
        '--timezone'  => 'UTC',
        '--admin'     => false,
    ])
        ->expectsQuestion('Set the Name for User', 'Admin')
        ->assertExitCode(0);
});

it('should validate database prefix in unopim:install command', function () {
    $this->app->extend(Installer::class, function ($service) {
        return new class extends Installer
        {
            public function call($command, array $arguments = [], $output = null)
            {
                return 0;
            }

            protected function databaseConnectionSuccessful(array $config): bool
            {
                return true;
            }

            protected function envUpdate(string $key, string $value): void
            { /* Mute */
            }

            public function handle()
            {
                // Ensure prompts occur even if .env exists
                $this->askForDatabaseDetails();

                return 0;
            }
        };
    });

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', 'too_long_prefix_')
        ->assertExitCode(1);

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', 'invalid-char!')
        ->assertExitCode(1);

    $this->artisan('unopim:install', ['--skip-admin-creation' => true])
        ->expectsQuestion('Please select the database connection', 'mysql')
        ->expectsQuestion('Please enter the database host', '127.0.0.1')
        ->expectsQuestion('Please enter the database port', '3306')
        ->expectsQuestion('Please enter the database name', 'unopim')
        ->expectsQuestion('Please enter the database prefix', 'uno_')
        ->expectsQuestion('Please enter your database username', 'root')
        ->expectsQuestion('Please enter your database password', 'root')
        ->assertExitCode(0);
});
