<?php

use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\User\Models\Admin;

it('uses the branded UnoPim subject for the reset password mail', function () {
    $admin = Admin::query()->first();

    $mail = (new ResetPasswordNotification('sample-token'))->toMail($admin);

    expect($mail->subject)->toBe(trans('admin::app.emails.admin.forgot-password.subject'));
});

it('renders the reset password mail aligned with the UnoPim design', function () {
    $admin = Admin::query()->first();

    $html = (string) (new ResetPasswordNotification('sample-token'))
        ->toMail($admin)
        ->render();

    expect($html)
        ->toContain('logo')
        ->toContain(trans('admin::app.footer.copy-right'))
        ->toContain(route('admin.reset_password.create', 'sample-token'))
        ->toContain('#7C3AED')
        ->toContain("'Inter'")
        ->toContain('lang="'.str_replace('_', '-', app()->getLocale()).'"')
        ->toContain('dir="ltr"')
        ->toContain('role="presentation"')
        ->not->toContain('display: flex')
        ->not->toContain('text-transform: uppercase')
        ->not->toContain('href="mailto:"');
});

it('localises the greeting in the data transfer email template instead of hardcoding it', function () {
    $templateData = (object) [
        'meta'  => json_encode(['type' => 'import', 'code' => 'products']),
        'state' => 'completed',
    ];

    $html = view('admin::emails.data-transfer.index', ['templateData' => $templateData])->render();

    expect($html)
        ->toContain(trans('admin::app.emails.hello'))
        ->toContain("'Inter'");
});
