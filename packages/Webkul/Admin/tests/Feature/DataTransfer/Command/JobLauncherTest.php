<?php

use Webkul\DataTransfer\Models\JobInstances;

it('should run job successfully with valid jobId and user email Id', function () {
    $user = $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->artisan(sprintf('unopim:queue:work %s %s', $exportJob->id, $user->email))
        ->expectsOutputToContain('Started processing job')
        ->assertSuccessful();
});

it('clears the worker timeout alarm once the queue is drained', function () {
    $user = $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->artisan(sprintf('unopim:queue:work %s %s', $exportJob->id, $user->email))
        ->assertSuccessful()
        ->run();

    expect(pcntl_alarm(0))->toBe(0);
});

it('should fail when given queue invalid jobId', function () {
    $user = $this->loginAsAdmin();

    $this->artisan(sprintf('unopim:queue:work 45 %s', $user->email))
        ->expectsOutputToContain('Job not found given jobId.')
        ->assertFailed();
});

it('should fail when given an invalid user email Id', function () {
    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->artisan(sprintf('unopim:queue:work %s xyz@example.com', $exportJob->id))
        ->expectsOutputToContain('User not found given user email Id.')
        ->assertFailed();
});
