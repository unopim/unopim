<?php

use Illuminate\Support\Facades\Log;
use Webkul\Notification\Mail\UserNotify;

function failingUserNotify(): UserNotify
{
    return new UserNotify(
        recipients: ['ops@example.test'],
        emailSubject: 'Export finished',
        emailTemplate: 'admin::emails.data-transfer.index',
        templateData: ['templateData' => null],
    );
}

it('logs the template, subject and recipients when the queued mail fails', function () {
    $captured = [];

    Log::shouldReceive('error')
        ->once()
        ->andReturnUsing(function (string $message, array $context) use (&$captured): void {
            $captured = ['message' => $message, 'context' => $context];
        });

    failingUserNotify()->failed(new RuntimeException('530 5.7.1 Authentication required'));

    expect($captured['message'])->toContain('failed')
        ->and($captured['context']['template'])->toBe('admin::emails.data-transfer.index')
        ->and($captured['context']['subject'])->toBe('Export finished')
        ->and($captured['context']['recipients'])->toBe(['ops@example.test'])
        ->and($captured['context']['exception'])->toContain('530');
});

it('exposes the handler the queue calls when a job exhausts its attempts', function () {
    $handler = new ReflectionMethod(UserNotify::class, 'failed');

    expect($handler->isPublic())->toBeTrue()
        ->and($handler->getNumberOfParameters())->toBe(1)
        ->and((string) $handler->getParameters()[0]->getType())->toBe(Throwable::class);
});
